<?php

namespace App\Http\Controllers\Driver;

use App\Actions\Execution\UpdateStopStatus;
use App\Enums\StopStatus;
use App\Http\Controllers\Concerns\AuthorizesTripExecution;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\UpdateStopStatusRequest;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class StopStatusController extends Controller
{
    use AuthorizesTripExecution;

    /**
     * Move one of the trip's stops through its workflow.
     */
    public function update(
        UpdateStopStatusRequest $request,
        Team $current_team,
        Trip $trip,
        Stop $stop,
        UpdateStopStatus $updateStatus,
    ): RedirectResponse {
        $this->authorizeTripExecution($request, $current_team, $trip);

        $data = $request->validated();

        $updateStatus->handle($stop, StopStatus::from($data['status']), $data['notes'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stop updated.')]);

        return back();
    }
}
