<?php

namespace App\Http\Controllers\Parties;

use App\Enums\PartyType;
use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parties\SavePartyRequest;
use App\Models\Location;
use App\Models\Party;
use App\Models\PartyContact;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PartyController extends Controller
{
    /**
     * Display the customer, carrier, and supplier directory.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('parties/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the directory with the given party open.
     */
    public function show(Request $request, Team $current_team, Party $party): Response
    {
        return Inertia::render('parties/Index', [
            ...$this->pageProps($request, $current_team),
            'party' => $this->detail($party),
        ]);
    }

    /**
     * Store a newly created party.
     */
    public function store(SavePartyRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $party = $current_team->parties()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $party->name])]);

        return to_route('parties.show', [
            'current_team' => $current_team->slug,
            'party' => $party->id,
        ]);
    }

    /**
     * Update the given party.
     */
    public function update(SavePartyRequest $request, Team $current_team, Party $party): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $party->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $party->name])]);

        return back();
    }

    /**
     * Remove the given party from the directory.
     */
    public function destroy(Team $current_team, Party $party): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $party->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $party->name])]);

        return to_route('parties.index', ['current_team' => $current_team->slug]);
    }

    /**
     * Get the props shared by the index and show pages.
     *
     * @return array<string, mixed>
     */
    protected function pageProps(Request $request, Team $team): array
    {
        $search = $request->string('search')->trim()->value() ?: null;
        $type = $request->query('type');

        return [
            'parties' => Party::query()
                ->withCount(['contacts', 'locations'])
                ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(legal_name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(rfc) LIKE ?', ['%'.mb_strtolower($search).'%'])))
                ->when(
                    is_string($type) && in_array($type, PartyType::values(), true),
                    fn ($query) => $query->where('type', $type),
                )
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Party $party) => $this->summary($party)),
            'filters' => [
                'search' => $search,
                'type' => is_string($type) && in_array($type, PartyType::values(), true) ? $type : null,
            ],
            'types' => PartyType::options(),
            'can' => [
                'manage' => $request->user()->hasTeamPermission($team, TeamPermission::ManageCatalog),
            ],
        ];
    }

    /**
     * Get the directory row for a party.
     *
     * @return array<string, mixed>
     */
    protected function summary(Party $party): array
    {
        return [
            'id' => $party->id,
            'type' => $party->type->value,
            'type_label' => $party->type->label(),
            'name' => $party->name,
            'legal_name' => $party->legal_name,
            'rfc' => $party->rfc,
            'email' => $party->email,
            'phone' => $party->phone,
            'contacts_count' => (int) $party->contacts_count,
            'locations_count' => (int) $party->locations_count,
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @return array<string, mixed>
     */
    protected function detail(Party $party): array
    {
        $party->load([
            'contacts' => fn ($query) => $query->orderBy('name'),
            'locations' => fn ($query) => $query->orderBy('name'),
        ]);

        return [
            ...$this->summary($party),
            'contacts_count' => $party->contacts->count(),
            'locations_count' => $party->locations->count(),
            'contacts' => $party->contacts
                ->map(fn (PartyContact $contact) => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'position' => $contact->position,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                ])
                ->all(),
            'locations' => $party->locations
                ->map(fn (Location $location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'city' => $location->city,
                    'state' => $location->state,
                ])
                ->all(),
        ];
    }
}
