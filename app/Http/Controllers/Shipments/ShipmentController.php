<?php

namespace App\Http\Controllers\Shipments;

use App\Enums\ShipmentStatus;
use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shipments\SaveShipmentRequest;
use App\Models\Location;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShipmentController extends Controller
{
    /**
     * Display the shipment board.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('shipments/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the shipment board with the given shipment open.
     */
    public function show(Request $request, Team $current_team, Shipment $shipment): Response
    {
        return Inertia::render('shipments/Index', [
            ...$this->pageProps($request, $current_team),
            'shipment' => $this->detail($shipment),
        ]);
    }

    /**
     * Update the given shipment.
     */
    public function update(SaveShipmentRequest $request, Team $current_team, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $data = $request->validated();
        $attributes = [];

        if (array_key_exists('pickup_location_id', $data)) {
            $pickup = $this->location($current_team, $data['pickup_location_id']);
            $attributes['pickup_location_id'] = $pickup?->id;
            $attributes['pickup_snapshot'] = $this->snapshot($pickup);
        }

        if (array_key_exists('delivery_location_id', $data)) {
            $delivery = $this->location($current_team, $data['delivery_location_id']);
            $attributes['delivery_location_id'] = $delivery?->id;
            $attributes['delivery_snapshot'] = $this->snapshot($delivery);
        }

        $shipment->update($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $shipment->number])]);

        return back();
    }

    /**
     * Remove the given shipment.
     */
    public function destroy(Team $current_team, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $shipment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $shipment->number])]);

        return to_route('shipments.index', ['current_team' => $current_team->slug]);
    }

    /**
     * Get the props shared by the index and show pages.
     *
     * @return array<string, mixed>
     */
    protected function pageProps(Request $request, Team $team): array
    {
        $search = $request->string('search')->trim()->value() ?: null;
        $status = $request->query('status');

        return [
            'shipments' => Shipment::query()
                ->with('order:id,number')
                ->withCount(['items', 'packages'])
                ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(number) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(customer_name) LIKE ?', ['%'.mb_strtolower($search).'%'])))
                ->when(
                    is_string($status) && in_array($status, ShipmentStatus::values(), true),
                    fn ($query) => $query->where('status', $status),
                )
                ->latest()
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Shipment $shipment) => $this->summary($shipment)),
            'filters' => [
                'search' => $search,
                'status' => is_string($status) && in_array($status, ShipmentStatus::values(), true) ? $status : null,
            ],
            'statuses' => ShipmentStatus::options(),
            'locations' => $team->locations()
                ->orderBy('name')
                ->get(['id', 'name', 'city'])
                ->map(fn ($location) => [
                    'value' => (string) $location->id,
                    'label' => $location->name.' · '.$location->city,
                ])
                ->all(),
            'can' => [
                'manage' => $request->user()->hasTeamPermission($team, TeamPermission::ManageOperations),
            ],
        ];
    }

    /**
     * Get the board row for a shipment.
     *
     * @return array<string, mixed>
     */
    protected function summary(Shipment $shipment): array
    {
        return [
            'id' => $shipment->id,
            'number' => $shipment->number,
            'status' => $shipment->status->value,
            'status_label' => $shipment->status->label(),
            'currency' => $shipment->currency,
            'customer_name' => $shipment->customer_name,
            'order_id' => $shipment->order_id,
            'order_number' => $shipment->order?->number,
            'pickup_location_id' => $shipment->pickup_location_id,
            'delivery_location_id' => $shipment->delivery_location_id,
            'pickup_snapshot' => $shipment->pickup_snapshot,
            'delivery_snapshot' => $shipment->delivery_snapshot,
            'weight_grams' => $shipment->weight_grams,
            'volume_cm3' => $shipment->volume_cm3,
            'pieces' => $shipment->pieces,
            'items_count' => (int) $shipment->items_count,
            'packages_count' => (int) $shipment->packages_count,
            'created_at' => $shipment->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @return array<string, mixed>
     */
    protected function detail(Shipment $shipment): array
    {
        $shipment->load([
            'items' => fn ($query) => $query->orderBy('id'),
            'packages' => fn ($query) => $query->orderBy('id'),
        ]);

        return [
            ...$this->summary($shipment),
            'items_count' => $shipment->items->count(),
            'packages_count' => $shipment->packages->count(),
            'package_limit' => (int) config('shipments.max_packages_per_shipment'),
            'items' => $shipment->items
                ->map(fn (ShipmentItem $item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'weight_grams' => $item->weight_grams,
                    'weight_kg' => $item->weight_grams / 1000,
                    'volume_cm3' => $item->volume_cm3,
                    'volume_m3' => $item->volume_cm3 / 1000000,
                    'hazmat' => $item->hazmat,
                ])
                ->all(),
            'packages' => $shipment->packages
                ->map(fn (Package $package) => [
                    'id' => $package->id,
                    'code' => $package->code,
                    'status' => $package->status->value,
                    'status_label' => $package->status->label(),
                    'weight_grams' => $package->weight_grams,
                ])
                ->all(),
        ];
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
