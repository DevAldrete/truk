<?php

namespace App\Http\Controllers\Driver;

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryOutcome;
use App\Enums\ExpenseType;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Enums\PackageStatus;
use App\Enums\ScanType;
use App\Enums\StopType;
use App\Enums\TeamPermission;
use App\Enums\TripStatus;
use App\Http\Controllers\Concerns\AuthorizesTripExecution;
use App\Http\Controllers\Controller;
use App\Models\DeliveryAttempt;
use App\Models\Expense;
use App\Models\Incident;
use App\Models\Package;
use App\Models\ProofOfDelivery;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The mobile-first driver portal: the trips assigned to the logged-in driver
 * and the execution screen for one of them.
 */
class DriverPortalController extends Controller
{
    use AuthorizesTripExecution;

    /**
     * List the driver's open trips.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('executeOperations', $current_team);

        $driver = $request->user()->driverProfileFor($current_team);
        $canManage = $request->user()->hasTeamPermission($current_team, TeamPermission::ManageOperations);

        $trips = Trip::query()
            ->whereIn('status', [
                TripStatus::Planned->value,
                TripStatus::Dispatched->value,
                TripStatus::InTransit->value,
            ])
            ->when(
                $driver !== null,
                fn ($query) => $query->where('driver_id', $driver->id),
                // A driver without a linked profile sees nothing; a planner sees all.
                fn ($query) => $canManage ? $query : $query->whereRaw('1 = 0'),
            )
            ->with(['vehicle:id,name,plate', 'trailer:id,name,plate'])
            ->withCount('stops')
            ->orderBy('planned_start_at')
            ->get()
            ->map(fn (Trip $trip): array => [
                'id' => $trip->id,
                'number' => $trip->number,
                'status' => $trip->status->value,
                'status_label' => $trip->status->label(),
                'planned_start_at' => $trip->planned_start_at?->toIso8601String(),
                'vehicle_name' => $trip->vehicle?->name,
                'trailer_name' => $trip->trailer?->name,
                'stops_count' => $trip->stops_count,
            ])
            ->all();

        return Inertia::render('driver/Trips', [
            'trips' => $trips,
            'driver' => $driver !== null ? ['id' => $driver->id, 'name' => $driver->name] : null,
        ]);
    }

    /**
     * Show the execution screen for one trip.
     */
    public function show(Request $request, Team $current_team, Trip $trip): Response
    {
        $this->authorizeTripExecution($request, $current_team, $trip);

        $trip->load([
            'vehicle:id,name,plate',
            'trailer:id,name,plate',
            'driver:id,name',
            'stops' => fn ($query) => $query->with([
                'location:id,name,city',
                'shipments' => fn ($shipment) => $shipment->with('packages')->orderBy('id'),
                'deliveryAttempts' => fn ($attempt) => $attempt->with('lines')->orderByDesc('occurred_at'),
                'proofsOfDelivery',
            ])->orderBy('sequence'),
            'incidents' => fn ($query) => $query->orderByDesc('occurred_at'),
            'expenses' => fn ($query) => $query->orderByDesc('incurred_at'),
        ]);

        return Inertia::render('driver/Trip', [
            'trip' => $this->tripDetail($trip, $current_team->slug),
            'options' => [
                'outcomes' => DeliveryOutcome::options(),
                'failureReasons' => DeliveryFailureReason::options(),
                'scanTypes' => ScanType::options(),
                'incidentTypes' => IncidentType::options(),
                'incidentSeverities' => IncidentSeverity::options(),
                'expenseTypes' => ExpenseType::options(),
            ],
        ]);
    }

    /**
     * Build the execution payload for a trip.
     *
     * @return array<string, mixed>
     */
    protected function tripDetail(Trip $trip, string $teamSlug): array
    {
        return [
            'id' => $trip->id,
            'number' => $trip->number,
            'status' => $trip->status->value,
            'status_label' => $trip->status->label(),
            'planned_start_at' => $trip->planned_start_at?->toIso8601String(),
            'timezone' => $trip->timezone,
            'notes' => $trip->notes,
            'driver_name' => $trip->driver?->name,
            'vehicle_name' => $trip->vehicle?->name,
            'vehicle_plate' => $trip->vehicle?->plate,
            'trailer_name' => $trip->trailer?->name,
            'stops' => $trip->stops->map(fn (Stop $stop): array => $this->stopDetail($stop, $teamSlug))->all(),
            'incidents' => $trip->incidents->map(fn (Incident $incident): array => $this->incidentSummary($incident))->all(),
            'expenses' => $trip->expenses->map(fn (Expense $expense): array => $this->expenseSummary($expense))->all(),
        ];
    }

    /**
     * Build the execution payload for a stop.
     *
     * @return array<string, mixed>
     */
    protected function stopDetail(Stop $stop, string $teamSlug): array
    {
        return [
            'id' => $stop->id,
            'sequence' => $stop->sequence,
            'type' => $stop->type->value,
            'type_label' => $stop->type->label(),
            'status' => $stop->status->value,
            'status_label' => $stop->status->label(),
            'is_pickup' => $stop->type === StopType::Pickup,
            'location_name' => $stop->location?->name,
            'location_snapshot' => $stop->location_snapshot,
            'planned_at' => $stop->planned_at?->toIso8601String(),
            'notes' => $stop->notes,
            'shipments' => $stop->shipments->map(fn (Shipment $shipment): array => $this->shipmentDetail($shipment))->all(),
            'attempts' => $stop->deliveryAttempts->map(fn (DeliveryAttempt $attempt): array => [
                'id' => $attempt->id,
                'outcome' => $attempt->outcome->value,
                'outcome_label' => $attempt->outcome->label(),
                'failure_reason_label' => $attempt->failure_reason?->label(),
                'recipient_name' => $attempt->recipient_name,
                'occurred_at' => $attempt->occurred_at->toIso8601String(),
                'notes' => $attempt->notes,
            ])->all(),
            'pods' => $stop->proofsOfDelivery->map(fn (ProofOfDelivery $pod): array => [
                'id' => $pod->id,
                'recipient_name' => $pod->recipient_name,
                'captured_at' => $pod->captured_at->toIso8601String(),
                'signature_url' => $pod->signature_path !== null
                    ? route('driver.pods.signature', ['current_team' => $teamSlug, 'pod' => $pod->id])
                    : null,
                'photo_count' => count($pod->photos ?? []),
                'document_count' => count($pod->document_paths ?? []),
            ])->all(),
        ];
    }

    /**
     * Build the execution payload for a shipment.
     *
     * @return array<string, mixed>
     */
    protected function shipmentDetail(Shipment $shipment): array
    {
        $delivered = (int) $shipment->deliveryAttemptLines()
            ->where('success', true)
            ->sum('quantity');

        return [
            'id' => $shipment->id,
            'number' => $shipment->number,
            'customer_name' => $shipment->customer_name,
            'status' => $shipment->status->value,
            'status_label' => $shipment->status->label(),
            'pieces' => $shipment->pieces,
            'delivered_quantity' => $delivered,
            'remaining_quantity' => max($shipment->pieces - $delivered, 0),
            'packages' => $shipment->packages->map(fn (Package $package): array => [
                'id' => $package->id,
                'code' => $package->code,
                'status' => $package->status->value,
                'status_label' => $package->status->label(),
                'terminal' => in_array($package->status, [PackageStatus::Delivered, PackageStatus::Returned, PackageStatus::Damaged], true),
            ])->all(),
        ];
    }

    /**
     * Build the summary of an incident.
     *
     * @return array<string, mixed>
     */
    protected function incidentSummary(Incident $incident): array
    {
        return [
            'id' => $incident->id,
            'type' => $incident->type->value,
            'type_label' => $incident->type->label(),
            'severity' => $incident->severity->value,
            'severity_label' => $incident->severity->label(),
            'status' => $incident->status->value,
            'status_label' => $incident->status->label(),
            'description' => $incident->description,
            'occurred_at' => $incident->occurred_at->toIso8601String(),
        ];
    }

    /**
     * Build the summary of an expense.
     *
     * @return array<string, mixed>
     */
    protected function expenseSummary(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'type' => $expense->type->value,
            'type_label' => $expense->type->label(),
            'amount_minor' => $expense->amount_minor,
            'currency' => $expense->currency,
            'incurred_at' => $expense->incurred_at->toIso8601String(),
            'vendor' => $expense->vendor,
        ];
    }
}
