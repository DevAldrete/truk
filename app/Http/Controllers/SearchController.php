<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Party;
use App\Models\Team;
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

        return response()->json([
            'results' => $parties->concat($locations)->values()->all(),
        ]);
    }
}
