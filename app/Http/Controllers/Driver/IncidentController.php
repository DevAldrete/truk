<?php

namespace App\Http\Controllers\Driver;

use App\Actions\Execution\ReportIncident;
use App\Http\Controllers\Concerns\AuthorizesTripExecution;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\ReportIncidentRequest;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class IncidentController extends Controller
{
    use AuthorizesTripExecution;

    /**
     * Report an incident against the trip.
     */
    public function store(
        ReportIncidentRequest $request,
        Team $current_team,
        Trip $trip,
        ReportIncident $report,
    ): RedirectResponse {
        $this->authorizeTripExecution($request, $current_team, $trip);

        $report->handle($current_team, $trip, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident reported.')]);

        return back();
    }
}
