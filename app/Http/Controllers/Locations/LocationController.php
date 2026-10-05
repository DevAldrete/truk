<?php

namespace App\Http\Controllers\Locations;

use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Locations\SaveLocationRequest;
use App\Models\Location;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * Display the pickup and delivery site directory.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('locations/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the directory with the given site open.
     */
    public function show(Request $request, Team $current_team, Location $location): Response
    {
        return Inertia::render('locations/Index', [
            ...$this->pageProps($request, $current_team),
            'location' => $this->detail($location),
        ]);
    }

    /**
     * Store a newly created site.
     */
    public function store(SaveLocationRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $location = $current_team->locations()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $location->name])]);

        return to_route('locations.show', [
            'current_team' => $current_team->slug,
            'location' => $location->id,
        ]);
    }

    /**
     * Update the given site.
     */
    public function update(SaveLocationRequest $request, Team $current_team, Location $location): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $location->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $location->name])]);

        return back();
    }

    /**
     * Remove the given site from the directory.
     */
    public function destroy(Team $current_team, Location $location): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $location->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $location->name])]);

        return to_route('locations.index', ['current_team' => $current_team->slug]);
    }

    /**
     * Get the props shared by the index and show pages.
     *
     * @return array<string, mixed>
     */
    protected function pageProps(Request $request, Team $team): array
    {
        $search = $request->string('search')->trim()->value() ?: null;

        return [
            'locations' => Location::query()
                ->with('party:id,name')
                ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(city) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(street) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(postal_code) LIKE ?', ['%'.mb_strtolower($search).'%'])))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Location $location) => $this->summary($location)),
            'filters' => [
                'search' => $search,
            ],
            'parties' => $team->parties()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($party) => ['value' => (string) $party->id, 'label' => $party->name])
                ->all(),
            'can' => [
                'manage' => $request->user()->hasTeamPermission($team, TeamPermission::ManageCatalog),
            ],
        ];
    }

    /**
     * Get the directory row for a site.
     *
     * @return array<string, mixed>
     */
    protected function summary(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'party_id' => $location->party_id,
            'party_name' => $location->party?->name,
            'street' => $location->street,
            'exterior_number' => $location->exterior_number,
            'interior_number' => $location->interior_number,
            'neighborhood' => $location->neighborhood,
            'city' => $location->city,
            'state' => $location->state,
            'postal_code' => $location->postal_code,
            'references' => $location->references,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'timezone' => $location->timezone,
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @return array<string, mixed>
     */
    protected function detail(Location $location): array
    {
        $location->loadMissing('party:id,name');

        return $this->summary($location);
    }
}
