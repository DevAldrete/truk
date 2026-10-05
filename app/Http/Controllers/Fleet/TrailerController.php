<?php

namespace App\Http\Controllers\Fleet;

use App\Enums\ComplianceDocumentType;
use App\Enums\PartyType;
use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\SaveTrailerRequest;
use App\Models\ComplianceDocument;
use App\Models\Team;
use App\Models\Trailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TrailerController extends Controller
{
    /**
     * Display the trailer roster.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('fleet/trailers/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the roster with the given trailer open.
     */
    public function show(Request $request, Team $current_team, Trailer $trailer): Response
    {
        return Inertia::render('fleet/trailers/Index', [
            ...$this->pageProps($request, $current_team),
            'trailer' => $this->detail($trailer),
        ]);
    }

    /**
     * Store a newly created trailer.
     */
    public function store(SaveTrailerRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $trailer = $current_team->trailers()->create($this->payload($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $trailer->name])]);

        return to_route('trailers.show', [
            'current_team' => $current_team->slug,
            'trailer' => $trailer->id,
        ]);
    }

    /**
     * Update the given trailer.
     */
    public function update(SaveTrailerRequest $request, Team $current_team, Trailer $trailer): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $trailer->update($this->payload($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $trailer->name])]);

        return back();
    }

    /**
     * Remove the given trailer from the roster.
     */
    public function destroy(Team $current_team, Trailer $trailer): RedirectResponse
    {
        Gate::authorize('manageCatalog', $current_team);

        $trailer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $trailer->name])]);

        return to_route('trailers.index', ['current_team' => $current_team->slug]);
    }

    /**
     * Get the fillable trailer attributes from the request.
     *
     * @return array<string, mixed>
     */
    protected function payload(SaveTrailerRequest $request): array
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
            'trailers' => Trailer::query()
                ->with(['carrierParty:id,name', 'documents'])
                ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(plate) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(configuration) LIKE ?', ['%'.mb_strtolower($search).'%'])))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Trailer $trailer) => $this->summary($trailer)),
            'filters' => [
                'search' => $search,
            ],
            'carriers' => $team->parties()
                ->where('type', PartyType::Carrier->value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($party) => ['value' => (string) $party->id, 'label' => $party->name])
                ->all(),
            'documentTypes' => ComplianceDocumentType::options(),
            'can' => [
                'manage' => $request->user()->hasTeamPermission($team, TeamPermission::ManageCatalog),
            ],
        ];
    }

    /**
     * Get the roster row for a trailer.
     *
     * @return array<string, mixed>
     */
    protected function summary(Trailer $trailer): array
    {
        return [
            'id' => $trailer->id,
            'name' => $trailer->name,
            'plate' => $trailer->plate,
            'configuration' => $trailer->configuration,
            'carrier_party_id' => $trailer->carrier_party_id,
            'carrier_name' => $trailer->carrierParty?->name,
            'max_payload_grams' => $trailer->max_payload_grams,
            'max_payload_kg' => $trailer->max_payload_kg,
            'max_volume_cm3' => $trailer->max_volume_cm3,
            'max_volume_m3' => $trailer->max_volume_m3,
            'documents_count' => $trailer->documents->count(),
            'has_expired_documents' => $trailer->documents->contains(fn (ComplianceDocument $document) => $document->expires_at?->isPast() ?? false),
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @return array<string, mixed>
     */
    protected function detail(Trailer $trailer): array
    {
        $trailer->loadMissing(['carrierParty:id,name', 'documents']);

        return [
            ...$this->summary($trailer),
            'documents' => $trailer->documents
                ->sortBy('expires_at')
                ->map(fn (ComplianceDocument $document) => $document->toSummary())
                ->values()
                ->all(),
        ];
    }
}
