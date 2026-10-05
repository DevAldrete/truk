<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\Party;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates orders together with their line items.
 *
 * The order number is assigned by the server, never by the client, and the
 * customer's name and RFC are snapshotted so a later edit to the party cannot
 * rewrite commercial history.
 */
class SaveOrder
{
    /**
     * Create an order with its items.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): Order
    {
        return DB::transaction(function () use ($team, $data): Order {
            $customer = $this->customer($team, $data['customer_party_id'] ?? null);

            $order = $team->orders()->create([
                ...$this->attributes($data, $customer),
                'number' => $this->nextNumber($team),
            ]);

            $this->syncItems($team, $order, $data['items'] ?? []);

            return $order;
        });
    }

    /**
     * Update an order and replace its items.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Team $team, Order $order, array $data): Order
    {
        return DB::transaction(function () use ($team, $order, $data): Order {
            $customer = $this->customer($team, $data['customer_party_id'] ?? null);

            $order->update($this->attributes($data, $customer));

            // Shipment lines keep a nullable reference to the order line they
            // fulfil; deleting the line releases it (FK ON DELETE SET NULL).
            $order->items()->delete();

            $this->syncItems($team, $order, $data['items'] ?? []);

            return $order;
        });
    }

    /**
     * Get the order attributes shared by create and update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(array $data, ?Party $customer): array
    {
        return [
            'customer_party_id' => $customer?->id,
            'status' => $data['status'],
            'currency' => $data['currency'] ?? 'MXN',
            'requested_pickup_at' => $data['requested_pickup_at'] ?? null,
            'requested_delivery_at' => $data['requested_delivery_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'customer_name' => $customer?->name,
            'customer_rfc' => $customer?->rfc,
        ];
    }

    /**
     * Create the order lines.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncItems(Team $team, Order $order, array $items): void
    {
        foreach ($items as $item) {
            $team->orderItems()->create([
                'order_id' => $order->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'weight_grams' => $item['weight_grams'] ?? 0,
                'volume_cm3' => $item['volume_cm3'] ?? 0,
                'hazmat' => (bool) ($item['hazmat'] ?? false),
            ]);
        }
    }

    /**
     * Resolve the customer party when one was chosen.
     */
    protected function customer(Team $team, ?int $customerId): ?Party
    {
        return $customerId === null
            ? null
            : $team->parties()->find($customerId);
    }

    /**
     * Build the next order number for the team.
     */
    protected function nextNumber(Team $team): string
    {
        $count = Order::withTrashed()->where('team_id', $team->id)->count();

        return 'ORD-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
