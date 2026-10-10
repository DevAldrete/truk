<?php

namespace App\Http\Controllers\Incidents;

use App\Actions\Execution\UpdateIncidentStatus;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\UpdateIncidentStatusRequest;
use App\Models\Incident;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    /**
     * Display the incident triage board.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('incidents/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the board with the given incident open.
     */
    public function show(Request $request, Team $current_team, Incident $incident): Response
    {
        return Inertia::render('incidents/Index', [
            ...$this->pageProps($request, $current_team),
            'incident' => $this->detail($incident),
        ]);
    }

    /**
     * Move an incident through its workflow.
     */
    public function update(
        UpdateIncidentStatusRequest $request,
        Team $current_team,
        Incident $incident,
        UpdateIncidentStatus $updateStatus,
    ): RedirectResponse {
        Gate::authorize('manageOperations', $current_team);

        $data = $request->validated();

        $updateStatus->handle(
            $incident,
            IncidentStatus::from($data['status']),
            $request->user(),
            $data['resolution'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident updated.')]);

        return back();
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
        $severity = $request->query('severity');

        return [
            'incidents' => Incident::query()
                ->with(['trip:id,number', 'shipment:id,number', 'driver:id,name', 'resolver:id,name'])
                ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(description) LIKE ?', ['%'.mb_strtolower($search).'%'])))
                ->when(
                    is_string($status) && in_array($status, IncidentStatus::values(), true),
                    fn ($query) => $query->where('status', $status),
                )
                ->when(
                    is_string($severity) && in_array($severity, IncidentSeverity::values(), true),
                    fn ($query) => $query->where('severity', $severity),
                )
                ->orderByDesc('occurred_at')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Incident $incident) => $this->summary($incident)),
            'filters' => [
                'search' => $search,
                'status' => is_string($status) && in_array($status, IncidentStatus::values(), true) ? $status : null,
                'severity' => is_string($severity) && in_array($severity, IncidentSeverity::values(), true) ? $severity : null,
            ],
            'counts' => [
                'open' => Incident::query()
                    ->whereIn('status', [IncidentStatus::Open->value, IncidentStatus::Investigating->value])
                    ->count(),
            ],
            'statuses' => IncidentStatus::options(),
            'severities' => IncidentSeverity::options(),
            'types' => IncidentType::options(),
            'can' => [
                'manage' => $request->user()->hasTeamPermission($team, TeamPermission::ManageOperations),
            ],
        ];
    }

    /**
     * Get the board row for an incident.
     *
     * @return array<string, mixed>
     */
    protected function summary(Incident $incident): array
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
            'age_hours' => (int) $incident->occurred_at->diffInHours(now()),
            'trip_id' => $incident->trip_id,
            'trip_number' => $incident->trip?->number,
            'shipment_id' => $incident->shipment_id,
            'shipment_number' => $incident->shipment?->number,
            'driver_name' => $incident->driver?->name,
            'resolution' => $incident->resolution,
            'resolved_at' => $incident->resolved_at?->toIso8601String(),
            'resolved_by_name' => $incident->resolver?->name,
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @return array<string, mixed>
     */
    protected function detail(Incident $incident): array
    {
        $incident->load(['trip:id,number', 'shipment:id,number', 'driver:id,name', 'resolver:id,name']);

        return $this->summary($incident);
    }
}
