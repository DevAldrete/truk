<?php

namespace App\Actions\Orders;

use App\Actions\GenerateDocumentNumber;
use App\Enums\ShipmentStatus;
use App\Models\Location;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Turns an order into a shipment: copies the items, snapshots the pickup and
 * delivery addresses, and derives the weight/volume/pieces totals used by the
 * capacity guard. Optionally generates the shipment's packages.
 */
class ConvertOrderToShipment
{
    public function __construct(private GenerateDocumentNumber $numbers) {}

    /**
     * Create a shipment from the given order.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, Order $order, array $data = []): Shipment
    {
        return DB::transaction(function () use ($team, $order, $data): Shipment {
            $pickup = $this->location($team, $data['pickup_location_id'] ?? null);
            $delivery = $this->location($team, $data['delivery_location_id'] ?? null);

            $order->loadMissing('items');

            $shipment = $team->shipments()->create([
                'order_id' => $order->id,
                'number' => $this->numbers->handle($team, Shipment::withTrashed(), 'SHP'),
                'status' => ShipmentStatus::Planned,
                'currency' => $order->currency,
                'pickup_location_id' => $pickup?->id,
                'delivery_location_id' => $delivery?->id,
                'pickup_snapshot' => $this->snapshot($pickup),
                'delivery_snapshot' => $this->snapshot($delivery),
                'customer_name' => $order->customer_name,
                'weight_grams' => $order->items->sum('weight_grams'),
                'volume_cm3' => $order->items->sum('volume_cm3'),
                'pieces' => $order->items->sum('quantity'),
            ]);

            foreach ($order->items as $item) {
                $team->shipmentItems()->create([
                    'shipment_id' => $shipment->id,
                    'order_item_id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'weight_grams' => $item->weight_grams,
                    'volume_cm3' => $item->volume_cm3,
                    'hazmat' => $item->hazmat,
                ]);
            }

            $this->generatePackages($team, $shipment, (int) ($data['package_count'] ?? 0));

            return $shipment->load('items', 'packages');
        });
    }

    /**
     * Generate the given number of packages for the shipment.
     */
    protected function generatePackages(Team $team, Shipment $shipment, int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            $team->packages()->create([
                'shipment_id' => $shipment->id,
                'code' => sprintf('%s-%s-%03d', 'PKG', $shipment->id, $index),
            ]);
        }
    }

    /**
     * Resolve a location of the team, when one was chosen.
     */
    protected function location(Team $team, ?int $locationId): ?Location
    {
        return $locationId === null
            ? null
            : $team->locations()->find($locationId);
    }

    /**
     * Freeze the address data of a location for the shipment snapshot.
     *
     * @return array<string, mixed>|null
     */
    protected function snapshot(?Location $location): ?array
    {
        if ($location === null) {
            return null;
        }

        return [
            'name' => $location->name,
            'street' => $location->street,
            'exterior_number' => $location->exterior_number,
            'interior_number' => $location->interior_number,
            'neighborhood' => $location->neighborhood,
            'city' => $location->city,
            'state' => $location->state,
            'postal_code' => $location->postal_code,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
        ];
    }
}
