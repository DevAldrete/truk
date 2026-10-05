<?php

namespace App\Http\Controllers\Driver;

use App\Actions\Execution\RecordDeliveryAttempt;
use App\Http\Controllers\Concerns\AuthorizesTripExecution;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\RecordDeliveryAttemptRequest;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DeliveryAttemptController extends Controller
{
    use AuthorizesTripExecution;

    /**
     * Record a delivery attempt at one of the trip's stops.
     */
    public function store(
        RecordDeliveryAttemptRequest $request,
        Team $current_team,
        Trip $trip,
        Stop $stop,
        RecordDeliveryAttempt $record,
    ): RedirectResponse {
        $this->authorizeTripExecution($request, $current_team, $trip);

        $record->handle($current_team, $stop, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delivery attempt recorded.')]);

        return back();
    }
}
