<?php

namespace App\Http\Controllers;

use App\Enums\IncidentStatus;
use App\Enums\ShipmentStatus;
use App\Enums\StopStatus;
use App\Enums\TripStatus;
use App\Models\ComplianceDocument;
use App\Models\Driver;
use App\Models\Incident;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\StopShipment;
use App\Models\TeamInvitation;
use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $email = strtolower((string) $request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $assignedShipmentIds = StopShipment::query()->pluck('shipment_id')->unique()->all();

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'operations' => [
                'dispatching_today' => Trip::query()
                    ->whereIn('status', [TripStatus::Dispatched->value, TripStatus::InTransit->value])
                    ->whereDate('planned_start_at', today())
                    ->count(),
                'delayed_stops' => Stop::query()
                    ->whereIn('status', [StopStatus::Pending->value, StopStatus::Arrived->value])
                    ->whereNotNull('planned_at')
                    ->where('planned_at', '<', now())
                    ->count(),
                'open_incidents' => Incident::query()
                    ->whereIn('status', [IncidentStatus::Open->value, IncidentStatus::Investigating->value])
                    ->count(),
                'unassigned_shipments' => Shipment::query()
                    ->whereIn('status', [ShipmentStatus::Planned->value, ShipmentStatus::Failed->value])
                    ->whereNotIn('id', $assignedShipmentIds)
                    ->count(),
            ],
            'fleetWarnings' => [
                'expired_licenses' => Driver::query()
                    ->whereNotNull('license_expires_at')
                    ->whereDate('license_expires_at', '<', now())
                    ->count(),
                'expired_documents' => ComplianceDocument::query()
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<', now())
                    ->count(),
                'expiring_documents' => ComplianceDocument::query()
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '>=', now())
                    ->whereDate('expires_at', '<=', now()->addDays(30))
                    ->count(),
            ],
        ]);
    }
}
