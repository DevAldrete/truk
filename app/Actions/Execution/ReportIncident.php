<?php

namespace App\Actions\Execution;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Reports an incident against a trip.
 *
 * Idempotent on the client key so a retried offline report returns the existing
 * record. The reporting driver is taken from the trip, never the client.
 */
class ReportIncident
{
    /**
     * Create the incident.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, Trip $trip, array $data, ?User $user = null): Incident
    {
        return DB::transaction(function () use ($team, $trip, $data, $user): Incident {
            $existing = $team->incidents()
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return $team->incidents()->create([
                'trip_id' => $trip->id,
                'stop_id' => $data['stop_id'] ?? null,
                'shipment_id' => $data['shipment_id'] ?? null,
                'driver_id' => $trip->driver_id,
                'type' => $data['type'],
                'severity' => $data['severity'],
                'status' => IncidentStatus::Open,
                'description' => $data['description'],
                'occurred_at' => $data['occurred_at'],
                'idempotency_key' => $data['idempotency_key'],
                'reported_by' => $user?->id,
            ]);
        });
    }
}
