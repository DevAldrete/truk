<?php

namespace App\Actions\Trips;

use App\Enums\TeamPermission;
use App\Enums\TripStatus;
use App\Models\Team;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Dispatches a planned trip behind the two hard gates: capacity and compliance.
 *
 * Both gates are enforced here so the dedicated dispatch endpoint and the trip
 * planner cannot drift. An over-capacity or non-compliant trip is blocked
 * unless the user holds the matching override permission and records a reason;
 * the override is persisted with who and when.
 */
class DispatchTrip
{
    public function __construct(
        private ComputeTripCapacity $capacity,
        private AssertTripCompliance $compliance,
        private SyncTripShipments $syncShipments,
    ) {}

    /**
     * Apply the gates and dispatch the trip.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, Trip $trip, array $data, ?User $user): Trip
    {
        if ($user === null) {
            throw ValidationException::withMessages([
                'status' => __('You cannot dispatch this trip.'),
            ]);
        }

        $attributes = ['status' => TripStatus::Dispatched->value];

        $attributes = [
            ...$attributes,
            ...$this->capacityOverride($team, $trip, $data, $user),
            ...$this->complianceOverride($team, $trip, $data, $user),
        ];

        $trip->forceFill($attributes)->save();

        $this->syncShipments->handle($trip);

        return $trip;
    }

    /**
     * Evaluate the capacity gate and build its override attributes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function capacityOverride(Team $team, Trip $trip, array $data, User $user): array
    {
        if (! $this->capacity->handle($trip)['over']) {
            return [
                'capacity_override_reason' => null,
                'capacity_overridden_by' => null,
                'capacity_overridden_at' => null,
            ];
        }

        if (! $user->hasTeamPermission($team, TeamPermission::OverrideCapacity)) {
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

        return [
            'capacity_override_reason' => $reason,
            'capacity_overridden_by' => $user->id,
            'capacity_overridden_at' => now(),
        ];
    }

    /**
     * Evaluate the compliance gate and build its override attributes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function complianceOverride(Team $team, Trip $trip, array $data, User $user): array
    {
        $violations = $this->compliance->handle($trip);

        if ($violations === []) {
            return [
                'compliance_override_reason' => null,
                'compliance_overridden_by' => null,
                'compliance_overridden_at' => null,
            ];
        }

        if (! $user->hasTeamPermission($team, TeamPermission::OverrideCompliance)) {
            throw ValidationException::withMessages([
                'status' => __('The trip does not pass the compliance gate: :reasons', [
                    'reasons' => collect($violations)->pluck('message')->implode(' '),
                ]),
            ]);
        }

        $reason = $data['compliance_override_reason'] ?? null;

        if (blank($reason)) {
            throw ValidationException::withMessages([
                'compliance_override_reason' => __('A reason is required to override the compliance gate.'),
            ]);
        }

        return [
            'compliance_override_reason' => $reason,
            'compliance_overridden_by' => $user->id,
            'compliance_overridden_at' => now(),
        ];
    }
}
