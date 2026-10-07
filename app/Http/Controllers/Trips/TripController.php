<?php

namespace App\Http\Controllers\Trips;

use App\Actions\Trips\ComputeTripCapacity;
use App\Actions\Trips\SaveTrip;
use App\Actions\Trips\SyncTripShipments;
use App\Enums\StopStatus;
use App\Enums\StopType;
use App\Enums\TeamPermission;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\SaveTripRequest;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use App\Models\TripAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    /**
     * Display the dispatch planner.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('trips/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the dispatch planner with the given trip open.
     */
    public function show(Request $request, Team $current_team, Trip $trip, ComputeTripCapacity $capacity): Response
    {
        return Inertia::render('trips/Index', [
            ...$this->pageProps($request, $current_team),
            'trip' => $this->detail($trip, $capacity->handle($trip)),
        ]);
    }

    /**
     * Store a newly created trip.
     */
    public function store(SaveTripRequest $request, Team $current_team, SaveTrip $saveTrip): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $trip = $saveTrip->create($current_team, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $trip->number])]);

        return to_route('trips.show', [
            'current_team' => $current_team->slug,
            'trip' => $trip->id,
        ]);
    }

    /**
     * Update the given trip.
     */
    public function update(SaveTripRequest $request, Team $current_team, Trip $trip, SaveTrip $saveTrip, ComputeTripCapacity $capacity, SyncTripShipments $syncShipments): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $data = $request->validated();
        $statusChanged = $data['status'] !== $trip->status->value;

        if ($statusChanged) {
            $target = TripStatus::from($data['status']);

            if (! $trip->status->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => __('That status change is not allowed.'),
                ]);
            }

            if ($target === TripStatus::Dispatched) {
                $this->assertCapacity($request, $current_team, $trip, $data, $capacity);
            }
        }

        $saveTrip->update($trip, $data);

        if ($statusChanged) {
            $syncShipments->handle($trip);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $trip->number])]);

        return back();
    }

    /**
     * Block an over-capacity dispatch unless it is overridden with a reason.
     *
     * @param  array<string, mixed>  $data
     */
    protected function assertCapacity(Request $request, Team $team, Trip $trip, array $data, ComputeTripCapacity $capacity): void
    {
        if (! $capacity->handle($trip)['over']) {
            return;
        }

        $user = $request->user();

        if ($user === null || ! $user->hasTeamPermission($team, TeamPermission::OverrideCapacity)) {
            throw ValidationException::withMessages([
                'status' => __('This trip is over capacity and you cannot override it.'),
            ]);
        }

        $reason = $data['capacity_override_reason'] ?? null;

        if (blank($reason)) {
            throw ValidationException::withMessages([
                'capacity_override_reason' => __('A reason is required to dispatch an over-capacity trip.'),
            ]);
        }

        $trip->forceFill([
            'capacity_override_reason' => $reason,
            'capacity_overridden_by' => $user->id,
            'capacity_overridden_at' => now(),
        ]);
    }

    /**
     * Remove the given trip.
     */
    public function destroy(Team $current_team, Trip $trip): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $trip->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $trip->number])]);

        return to_route('trips.index', ['current_team' => $current_team->slug]);
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
            'trips' => Trip::query()
                ->with(['driver:id,name', 'vehicle:id,name', 'trailer:id,name'])
                ->when($search, fn ($query, string $search) => $query->whereRaw('LOWER(number) LIKE ?', ['%'.mb_strtolower($search).'%']))
                ->when(
                    is_string($status) && in_array($status, TripStatus::values(), true),
                    fn ($query) => $query->where('status', $status),
                )
                ->orderByDesc('planned_start_at')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Trip $trip) => $this->summary($trip)),
            'filters' => [
                'search' => $search,
                'status' => is_string($status) && in_array($status, TripStatus::values(), true) ? $status : null,
            ],
            'statuses' => TripStatus::options(),
            'drivers' => $team->drivers()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($driver) => ['value' => (string) $driver->id, 'label' => $driver->name])->all(),
            'vehicles' => $team->vehicles()->orderBy('name')->get(['id', 'name', 'plate'])
                ->map(fn ($vehicle) => ['value' => (string) $vehicle->id, 'label' => $vehicle->name.' · '.$vehicle->plate])->all(),
            'trailers' => $team->trailers()->orderBy('name')->get(['id', 'name', 'plate'])
                ->map(fn ($trailer) => ['value' => (string) $trailer->id, 'label' => $trailer->name.' · '.$trailer->plate])->all(),
            'locations' => $team->locations()->orderBy('name')->get(['id', 'name', 'city'])
                ->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name.' · '.$location->city])->all(),
            'shipments' => Shipment::query()->orderByDesc('id')->limit(100)->get(['id', 'number', 'customer_name'])
                ->map(fn (Shipment $shipment) => ['value' => (string) $shipment->id, 'label' => $shipment->number.' · '.($shipment->customer_name ?? __('No customer'))])->all(),
            'stopTypes' => StopType::options(),
            'stopStatuses' => StopStatus::options(),
            'can' => [
                'manage' => $request->user()->hasTeamPermission($team, TeamPermission::ManageOperations),
                'overrideCapacity' => $request->user()->hasTeamPermission($team, TeamPermission::OverrideCapacity),
            ],
        ];
    }

    /**
     * Get the planner row for a trip.
     *
     * @return array<string, mixed>
     */
    protected function summary(Trip $trip): array
    {
        return [
            'id' => $trip->id,
            'number' => $trip->number,
            'status' => $trip->status->value,
            'status_label' => $trip->status->label(),
            'planned_start_at' => $trip->planned_start_at?->toIso8601String(),
            'planned_end_at' => $trip->planned_end_at?->toIso8601String(),
            'timezone' => $trip->timezone,
            'notes' => $trip->notes,
            'driver_id' => $trip->driver_id,
            'driver_name' => $trip->driver?->name,
            'vehicle_id' => $trip->vehicle_id,
            'vehicle_name' => $trip->vehicle?->name,
            'trailer_id' => $trip->trailer_id,
            'trailer_name' => $trip->trailer?->name,
            'created_at' => $trip->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @param  array<string, mixed>  $capacity
     * @return array<string, mixed>
     */
    protected function detail(Trip $trip, array $capacity): array
    {
        $trip->load(['driver:id,name', 'vehicle:id,name', 'trailer:id,name']);
        $trip->load(['assignments' => fn ($query) => $query->with(['driver:id,name', 'vehicle:id,name', 'trailer:id,name'])->orderByDesc('id')]);
        $trip->load(['stops' => fn ($query) => $query->with(['location:id,name,city', 'shipments'])->orderBy('sequence')]);

        return [
            ...$this->summary($trip),
            'capacity' => $capacity,
            'capacity_override_reason' => $trip->capacity_override_reason,
            'capacity_overridden_at' => $trip->capacity_overridden_at?->toIso8601String(),
            'assignments' => $trip->assignments
                ->map(fn (TripAssignment $assignment): array => [
                    'id' => $assignment->id,
                    'resource' => $this->resourceOf($assignment),
                    'name' => $this->resourceName($assignment),
                    'assigned_at' => $assignment->assigned_at->toIso8601String(),
                    'released_at' => $assignment->released_at?->toIso8601String(),
                ])
                ->all(),
            'stops' => $trip->stops
                ->map(fn (Stop $stop): array => [
                    'id' => $stop->id,
                    'sequence' => $stop->sequence,
                    'type' => $stop->type->value,
                    'type_label' => $stop->type->label(),
                    'status' => $stop->status->value,
                    'status_label' => $stop->status->label(),
                    'location_id' => $stop->location_id,
                    'location_name' => $stop->location?->name,
                    'location_snapshot' => $stop->location_snapshot,
                    'planned_at' => $stop->planned_at?->toIso8601String(),
                    'notes' => $stop->notes,
                    'shipments' => $stop->shipments
                        ->map(fn (Shipment $shipment): array => [
                            'id' => $shipment->id,
                            'number' => $shipment->number,
                            'customer_name' => $shipment->customer_name,
                        ])
                        ->all(),
                ])
                ->all(),
        ];
    }

    /**
     * Get which kind of resource the assignment row records.
     */
    protected function resourceOf(TripAssignment $assignment): string
    {
        return match (true) {
            $assignment->driver_id !== null => 'driver',
            $assignment->vehicle_id !== null => 'vehicle',
            default => 'trailer',
        };
    }

    /**
     * Get the name of the resource the assignment row records.
     */
    protected function resourceName(TripAssignment $assignment): ?string
    {
        return match ($this->resourceOf($assignment)) {
            'driver' => $assignment->driver?->name,
            'vehicle' => $assignment->vehicle?->name,
            default => $assignment->trailer?->name,
        };
    }
}
