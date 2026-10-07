<?php

namespace App\Http\Controllers\Trips;

use App\Actions\Trips\ComputeTripCapacity;
use App\Actions\Trips\SyncTripShipments;
use App\Enums\TeamPermission;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\DispatchTripRequest;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TripDispatchController extends Controller
{
    /**
     * Dispatch the given trip, enforcing the capacity gate.
     */
    public function store(
        DispatchTripRequest $request,
        Team $current_team,
        Trip $trip,
        ComputeTripCapacity $capacity,
        SyncTripShipments $syncShipments,
    ): RedirectResponse {
        Gate::authorize('manageOperations', $current_team);

        if ($trip->status !== TripStatus::Planned) {
            throw ValidationException::withMessages([
                'status' => __('Only a planned trip can be dispatched.'),
            ]);
        }

        $attributes = ['status' => TripStatus::Dispatched->value];

        if ($capacity->handle($trip)['over']) {
            $user = $request->user();

            if ($user === null || ! $user->hasTeamPermission($current_team, TeamPermission::OverrideCapacity)) {
                throw ValidationException::withMessages([
                    'status' => __('This trip is over capacity and you cannot override it.'),
                ]);
            }

            $reason = $request->validated('capacity_override_reason');

            if (blank($reason)) {
                throw ValidationException::withMessages([
                    'capacity_override_reason' => __('A reason is required to dispatch an over-capacity trip.'),
                ]);
            }

            $attributes['capacity_override_reason'] = $reason;
            $attributes['capacity_overridden_by'] = $user->id;
            $attributes['capacity_overridden_at'] = now();
        }

        $trip->forceFill($attributes)->save();

        $syncShipments->handle($trip);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $trip->number])]);

        return back();
    }
}
