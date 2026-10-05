<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\SaveComplianceDocumentRequest;
use App\Models\ComplianceDocument;
use App\Models\Driver;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DriverDocumentController extends Controller
{
    /**
     * Attach a document to the given driver.
     */
    public function store(SaveComplianceDocumentRequest $request, Team $current_team, Driver $driver): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $document = $driver->documents()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $document->type->label()])]);

        return back();
    }

    /**
     * Update the given driver document.
     */
    public function update(SaveComplianceDocumentRequest $request, Team $current_team, Driver $driver, ComplianceDocument $document): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $document->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $document->type->label()])]);

        return back();
    }

    /**
     * Remove the given driver document.
     */
    public function destroy(Team $current_team, Driver $driver, ComplianceDocument $document): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $document->type->label()])]);

        return back();
    }
}
