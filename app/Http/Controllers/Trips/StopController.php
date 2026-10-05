<?php

namespace App\Http\Controllers\Trips;

use App\Actions\Trips\SaveStop;
use App\Enums\StopStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\SaveStopRequest;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StopController extends Controller
{
    /**
     * Add a stop to the given trip.
     */
    public function store(SaveStopRequest $request, Team $current_team, Trip $trip, SaveStop $saveStop): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $stop = $saveStop->create($current_team, $trip, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $stop->type->label()])]);

        return back();
    }

    /**
     * Update the given stop.
     */
    public function update(SaveStopRequest $request, Team $current_team, Trip $trip, Stop $stop, SaveStop $saveStop): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $data = $request->validated();

        if ($data['status'] !== $stop->status->value) {
            $target = StopStatus::from($data['status']);

            if (! $stop->status->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => __('That status change is not allowed.'),
                ]);
            }
        }

        $saveStop->update($current_team, $stop, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $stop->type->label()])]);

        return back();
    }

    /**
     * Remove the given stop.
     */
    public function destroy(Team $current_team, Trip $trip, Stop $stop): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $stop->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $stop->type->label()])]);

        return back();
    }
}
