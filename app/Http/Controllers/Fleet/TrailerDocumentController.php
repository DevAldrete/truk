<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\SaveComplianceDocumentRequest;
use App\Models\ComplianceDocument;
use App\Models\Team;
use App\Models\Trailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TrailerDocumentController extends Controller
{
    /**
     * Attach a document to the given trailer.
     */
    public function store(SaveComplianceDocumentRequest $request, Team $current_team, Trailer $trailer): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $document = $trailer->documents()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $document->type->label()])]);

        return back();
    }

    /**
     * Update the given trailer document.
     */
    public function update(SaveComplianceDocumentRequest $request, Team $current_team, Trailer $trailer, ComplianceDocument $document): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $document->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $document->type->label()])]);

        return back();
    }

    /**
     * Remove the given trailer document.
     */
    public function destroy(Team $current_team, Trailer $trailer, ComplianceDocument $document): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $document->type->label()])]);

        return back();
    }
}
