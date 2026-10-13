<?php

namespace App\Http\Controllers\Dispatch;

use App\Actions\Trips\AssertTripCompliance;
use App\Actions\Trips\ComputeTripCapacity;
use App\Enums\ShipmentStatus;
use App\Enums\TeamPermission;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\StopShipment;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DispatchController extends Controller
{
    /**
     * Display the dispatch board: open trips on one side, unassigned
     * shipments on the other, with live capacity and conflicts.
     */
    public function __invoke(Request $request, Team $current_team, ComputeTripCapacity $capacity, AssertTripCompliance $compliance): Response
    {
        $openStatuses = [
            TripStatus::Planned->value,
            TripStatus::Dispatched->value,
            TripStatus::InTransit->value,
        ];

        $trips = Trip::query()
            ->whereIn('status', $openStatuses)
            ->with([
                'driver:id,name',
                'vehicle:id,name,plate',
                'trailer:id,name,plate',
                'stops' => fn ($query) => $query->with('shipments')->orderBy('sequence'),
            ])
            ->orderBy('planned_start_at')
            ->get()
            ->map(fn (Trip $trip) => $this->tripCard($trip, $capacity, $compliance))
            ->all();

        $stopIds = Stop::query()
            ->whereIn('trip_id', Trip::query()->whereIn('status', $openStatuses)->select('id'))
            ->pluck('id');

        $assignedIds = StopShipment::query()
            ->whereIn('stop_id', $stopIds)
            ->pluck('shipment_id')
            ->unique()
            ->all();

        return Inertia::render('dispatch/Index', [
            'trips' => $trips,
            'pool' => Shipment::query()
                ->whereIn('status', [
                    ShipmentStatus::Planned->value,
                    ShipmentStatus::Failed->value,
                ])
                ->whereNotIn('id', $assignedIds)
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (Shipment $shipment) => [
                    'id' => $shipment->id,
                    'number' => $shipment->number,
                    'customer_name' => $shipment->customer_name,
                    'status' => $shipment->status->value,
                    'status_label' => $shipment->status->label(),
                    'pieces' => $shipment->pieces,
                    'weight_grams' => $shipment->weight_grams,
                    'destination' => $shipment->delivery_snapshot['name'] ?? null,
                ])
                ->all(),
            'drivers' => $current_team->drivers()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($driver) => ['value' => (string) $driver->id, 'label' => $driver->name])->all(),
            'vehicles' => $current_team->vehicles()->orderBy('name')->get(['id', 'name', 'plate'])
                ->map(fn ($vehicle) => ['value' => (string) $vehicle->id, 'label' => $vehicle->name.' · '.$vehicle->plate])->all(),
            'trailers' => $current_team->trailers()->orderBy('name')->get(['id', 'name', 'plate'])
                ->map(fn ($trailer) => ['value' => (string) $trailer->id, 'label' => $trailer->name.' · '.$trailer->plate])->all(),
            'can' => [
                'manage' => $request->user()->hasTeamPermission($current_team, TeamPermission::ManageOperations),
                'overrideCapacity' => $request->user()->hasTeamPermission($current_team, TeamPermission::OverrideCapacity),
                'overrideCompliance' => $request->user()->hasTeamPermission($current_team, TeamPermission::OverrideCompliance),
            ],
        ]);
    }

    /**
     * Build the board card for a trip.
     *
     * @return array<string, mixed>
     */
    protected function tripCard(Trip $trip, ComputeTripCapacity $capacity, AssertTripCompliance $compliance): array
    {
        $shipments = $trip->stops
            ->flatMap(fn (Stop $stop) => $stop->shipments)
            ->unique('id')
            ->values();

        $violations = $compliance->handle($trip);

        return [
            'id' => $trip->id,
            'number' => $trip->number,
            'status' => $trip->status->value,
            'status_label' => $trip->status->label(),
            'planned_start_at' => $trip->planned_start_at?->toIso8601String(),
            'driver_id' => $trip->driver_id,
            'driver_name' => $trip->driver?->name,
            'vehicle_id' => $trip->vehicle_id,
            'vehicle_name' => $trip->vehicle?->name,
            'trailer_id' => $trip->trailer_id,
            'trailer_name' => $trip->trailer?->name,
            'capacity' => $capacity->handle($trip),
            'compliance' => [
                'ok' => $violations === [],
                'violations' => $violations,
            ],
            'shipments' => $shipments
                ->map(fn (Shipment $shipment) => [
                    'id' => $shipment->id,
                    'number' => $shipment->number,
                    'customer_name' => $shipment->customer_name,
                ])
                ->all(),
        ];
    }
}
