<?php

namespace App\Http\Controllers\Driver;

use App\Actions\Execution\RecordProofOfDelivery;
use App\Http\Controllers\Concerns\AuthorizesTripExecution;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\RecordProofOfDeliveryRequest;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProofOfDeliveryController extends Controller
{
    use AuthorizesTripExecution;

    /**
     * Record the proof of delivery for a stop.
     */
    public function store(
        RecordProofOfDeliveryRequest $request,
        Team $current_team,
        Trip $trip,
        Stop $stop,
        RecordProofOfDelivery $record,
    ): RedirectResponse {
        $this->authorizeTripExecution($request, $current_team, $trip);

        $record->handle($current_team, $stop, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proof of delivery saved.')]);

        return back();
    }
}
