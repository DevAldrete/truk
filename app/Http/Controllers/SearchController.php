<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Location;
use App\Models\Party;
use App\Models\Team;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search the team's records for the command palette.
     */
    public function __invoke(Request $request, Team $current_team): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $needle = '%'.mb_strtolower($term).'%';

        $parties = Party::query()
            ->where(fn ($query) => $query
                ->whereRaw('LOWER(name) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(legal_name) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(rfc) LIKE ?', [$needle]))
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Party $party) => [
                'type' => 'party',
                'title' => $party->name,
                'subtitle' => $party->type->label(),
                'url' => route('parties.show', [
                    'current_team' => $current_team->slug,
                    'party' => $party->id,
                ]),
            ]);

        $locations = Location::query()
            ->where(fn ($query) => $query
                ->whereRaw('LOWER(name) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(street) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(city) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(postal_code) LIKE ?', [$needle]))
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Location $location) => [
                'type' => 'location',
                'title' => $location->name,
                'subtitle' => $location->city.', '.$location->state,
                'url' => route('locations.show', [
                    'current_team' => $current_team->slug,
                    'location' => $location->id,
                ]),
            ]);

        $drivers = Driver::query()
            ->where(fn ($query) => $query
                ->whereRaw('LOWER(name) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(phone) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(license_number) LIKE ?', [$needle]))
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Driver $driver) => [
                'type' => 'driver',
                'title' => $driver->name,
                'subtitle' => $driver->license_number ?? $driver->phone,
                'url' => route('drivers.show', [
                    'current_team' => $current_team->slug,
                    'driver' => $driver->id,
                ]),
            ]);

        $vehicles = Vehicle::query()
            ->where(fn ($query) => $query
                ->whereRaw('LOWER(name) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(plate) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(configuration) LIKE ?', [$needle]))
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'type' => 'vehicle',
                'title' => $vehicle->name,
                'subtitle' => $vehicle->plate.' · '.$vehicle->configuration,
                'url' => route('vehicles.show', [
                    'current_team' => $current_team->slug,
                    'vehicle' => $vehicle->id,
                ]),
            ]);

        return response()->json([
            'results' => $parties->concat($locations)->concat($drivers)->concat($vehicles)->values()->all(),
        ]);
    }
}
