<?php

namespace App\Http\Controllers\Incidents;

use App\Actions\Execution\UpdateIncidentStatus;
use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\UpdateIncidentStatusRequest;
use App\Models\Incident;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class IncidentController extends Controller
{
    /**
     * Move an incident through its workflow.
     */
    public function update(
        UpdateIncidentStatusRequest $request,
        Team $current_team,
        Incident $incident,
        UpdateIncidentStatus $updateStatus,
    ): RedirectResponse {
        Gate::authorize('manageOperations', $current_team);

        $data = $request->validated();

        $updateStatus->handle(
            $incident,
            IncidentStatus::from($data['status']),
            $request->user(),
            $data['resolution'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident updated.')]);

        return back();
    }
}
