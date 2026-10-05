<?php

namespace App\Http\Controllers\Fleet;

use App\Enums\PartyType;
use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\SaveVehicleRequest;
use App\Models\Team;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * Display the vehicle roster.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('fleet/vehicles/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the roster with the given vehicle open.
     */
    public function show(Request $request, Team $current_team, Vehicle $vehicle): Response
    {
        return Inertia::render('fleet/vehicles/Index', [
            ...$this->pageProps($request, $current_team),
            'vehicle' => $this->detail($vehicle),
        ]);
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(SaveVehicleRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $vehicle = $current_team->vehicles()->create($this->payload($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $vehicle->name])]);

        return to_route('vehicles.show', [
            'current_team' => $current_team->slug,
            'vehicle' => $vehicle->id,
        ]);
    }

    /**
     * Update the given vehicle.
     */
    public function update(SaveVehicleRequest $request, Team $current_team, Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $vehicle->update($this->payload($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $vehicle->name])]);

        return back();
    }

    /**
     * Remove the given vehicle from the roster.
     */
    public function destroy(Team $current_team, Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $vehicle->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $vehicle->name])]);

        return to_route('vehicles.index', ['current_team' => $current_team->slug]);
    }

    /**
     * Get the fillable vehicle attributes from the request.
     *
     * @return array<string, mixed>
     */
    protected function payload(SaveVehicleRequest $request): array
    {
        return $request->safe()->only([
            'carrier_party_id',
            'name',
            'plate',
            'configuration',
            'max_payload_grams',
            'max_volume_cm3',
        ]);
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
            'vehicles' => Vehicle::query()
                ->with('carrierParty:id,name')
                ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(plate) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(configuration) LIKE ?', ['%'.mb_strtolower($search).'%'])))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Vehicle $vehicle) => $this->summary($vehicle)),
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
     * Get the roster row for a vehicle.
     *
     * @return array<string, mixed>
     */
    protected function summary(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'plate' => $vehicle->plate,
            'configuration' => $vehicle->configuration,
            'carrier_party_id' => $vehicle->carrier_party_id,
            'carrier_name' => $vehicle->carrierParty?->name,
            'max_payload_grams' => $vehicle->max_payload_grams,
            'max_payload_kg' => $vehicle->max_payload_kg,
            'max_volume_cm3' => $vehicle->max_volume_cm3,
            'max_volume_m3' => $vehicle->max_volume_m3,
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @return array<string, mixed>
     */
    protected function detail(Vehicle $vehicle): array
    {
        $vehicle->loadMissing('carrierParty:id,name');

        return $this->summary($vehicle);
    }
}
