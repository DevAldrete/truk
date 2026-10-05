<?php

namespace App\Http\Controllers\Fleet;

use App\Enums\PartyType;
use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\SaveDriverRequest;
use App\Models\Driver;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    /**
     * Display the driver roster.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('fleet/drivers/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the roster with the given driver open.
     */
    public function show(Request $request, Team $current_team, Driver $driver): Response
    {
        return Inertia::render('fleet/drivers/Index', [
            ...$this->pageProps($request, $current_team),
            'driver' => $this->detail($driver),
        ]);
    }

    /**
     * Store a newly created driver.
     */
    public function store(SaveDriverRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $driver = $current_team->drivers()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $driver->name])]);

        return to_route('drivers.show', [
            'current_team' => $current_team->slug,
            'driver' => $driver->id,
        ]);
    }

    /**
     * Update the given driver.
     */
    public function update(SaveDriverRequest $request, Team $current_team, Driver $driver): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $driver->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $driver->name])]);

        return back();
    }

    /**
     * Remove the given driver from the roster.
     */
    public function destroy(Team $current_team, Driver $driver): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $driver->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $driver->name])]);

        return to_route('drivers.index', ['current_team' => $current_team->slug]);
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
            'drivers' => Driver::query()
                ->with('carrierParty:id,name')
                ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(license_number) LIKE ?', ['%'.mb_strtolower($search).'%'])))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Driver $driver) => $this->summary($driver)),
            'filters' => [
                'search' => $search,
            ],
            'carriers' => $team->parties()
                ->where('type', PartyType::Carrier->value)
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
     * Get the roster row for a driver.
     *
     * @return array<string, mixed>
     */
    protected function summary(Driver $driver): array
    {
        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
            'license_number' => $driver->license_number,
            'license_expires_at' => $driver->license_expires_at?->toDateString(),
            'license_expired' => $driver->license_expires_at?->isPast() ?? false,
            'carrier_party_id' => $driver->carrier_party_id,
            'carrier_name' => $driver->carrierParty?->name,
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @return array<string, mixed>
     */
    protected function detail(Driver $driver): array
    {
        $driver->loadMissing('carrierParty:id,name');

        return $this->summary($driver);
    }
}
