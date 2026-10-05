<?php

namespace App\Http\Controllers\Driver;

use App\Actions\Execution\RecordExpense;
use App\Http\Controllers\Concerns\AuthorizesTripExecution;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\RecordExpenseRequest;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    use AuthorizesTripExecution;

    /**
     * Record an operational expense on the trip.
     */
    public function store(
        RecordExpenseRequest $request,
        Team $current_team,
        Trip $trip,
        RecordExpense $record,
    ): RedirectResponse {
        $this->authorizeTripExecution($request, $current_team, $trip);

        $record->handle($current_team, $trip, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense recorded.')]);

        return back();
    }
}
