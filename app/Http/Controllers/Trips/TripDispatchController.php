<?php

namespace App\Http\Controllers\Trips;

use App\Actions\Trips\DispatchTrip;
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
     * Dispatch the given trip, enforcing the capacity and compliance gates.
     */
    public function store(
        DispatchTripRequest $request,
        Team $current_team,
        Trip $trip,
        DispatchTrip $dispatch,
    ): RedirectResponse {
        Gate::authorize('manageOperations', $current_team);

        if ($trip->status !== TripStatus::Planned) {
            throw ValidationException::withMessages([
                'status' => __('Only a planned trip can be dispatched.'),
            ]);
        }

        $dispatch->handle($current_team, $trip, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $trip->number])]);

        return back();
    }
}
