<?php

namespace App\Http\Controllers\Parties;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parties\SavePartyContactRequest;
use App\Models\Party;
use App\Models\PartyContact;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PartyContactController extends Controller
{
    /**
     * Add a contact to the given party.
     */
    public function store(SavePartyContactRequest $request, Team $current_team, Party $party): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $contact = $current_team->partyContacts()->create([
            ...$request->validated(),
            'party_id' => $party->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $contact->name])]);

        return back();
    }

    /**
     * Update the given contact.
     */
    public function update(
        SavePartyContactRequest $request,
        Team $current_team,
        Party $party,
        PartyContact $contact,
    ): RedirectResponse {
        Gate::authorize('manageCatalog', $current_team);

        $contact->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $contact->name])]);

        return back();
    }

    /**
     * Remove the given contact.
     */
    public function destroy(Team $current_team, Party $party, PartyContact $contact): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $contact->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $contact->name])]);

        return back();
    }
}
