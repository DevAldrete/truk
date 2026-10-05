<?php

namespace App\Http\Controllers\Loads;

use App\Actions\Loads\SaveLoad;
use App\Enums\LoadStatus;
use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loads\SaveLoadRequest;
use App\Models\Load;
use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoadController extends Controller
{
    /**
     * Display the load planner.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('loads/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the load planner with the given load open.
     */
    public function show(Request $request, Team $current_team, Load $load): Response
    {
        return Inertia::render('loads/Index', [
            ...$this->pageProps($request, $current_team),
            'load' => $this->detail($load),
        ]);
    }

    /**
     * Store a newly created load.
     */
    public function store(SaveLoadRequest $request, Team $current_team, SaveLoad $saveLoad): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $load = $saveLoad->create($current_team, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $load->number])]);

        return to_route('loads.show', [
            'current_team' => $current_team->slug,
            'load' => $load->id,
        ]);
    }

    /**
     * Update the given load.
     */
    public function update(SaveLoadRequest $request, Team $current_team, Load $load, SaveLoad $saveLoad): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $data = $request->validated();

        if ($data['status'] !== $load->status->value) {
            $target = LoadStatus::from($data['status']);

            if (! $load->status->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => __('That status change is not allowed.'),
                ]);
            }
        }

        $saveLoad->update($load, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $load->number])]);

        return back();
    }

    /**
     * Remove the given load.
     */
    public function destroy(Team $current_team, Load $load): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $load->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $load->number])]);

        return to_route('loads.index', ['current_team' => $current_team->slug]);
    }

    /**
     * Get the props shared by the index and show pages.
     *
     * @return array<string, mixed>
     */
    protected function pageProps(Request $request, Team $team): array
    {
        $search = $request->string('search')->trim()->value() ?: null;
        $status = $request->query('status');

        return [
            'loads' => Load::query()
                ->withCount('shipments')
                ->when($search, fn ($query, string $search) => $query->whereRaw('LOWER(number) LIKE ?', ['%'.mb_strtolower($search).'%']))
                ->when(
                    is_string($status) && in_array($status, LoadStatus::values(), true),
                    fn ($query) => $query->where('status', $status),
                )
                ->latest()
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Load $load) => $this->summary($load)),
            'filters' => [
                'search' => $search,
                'status' => is_string($status) && in_array($status, LoadStatus::values(), true) ? $status : null,
            ],
            'statuses' => LoadStatus::options(),
            'availableShipments' => Shipment::query()
                ->whereNull('load_id')
                ->orderByDesc('id')
                ->limit(50)
                ->get(['id', 'number', 'customer_name', 'pieces'])
                ->map(fn (Shipment $shipment) => [
                    'value' => (string) $shipment->id,
                    'label' => $shipment->number.' · '.($shipment->customer_name ?? __('No customer')),
                ])
                ->all(),
            'can' => [
                'manage' => $request->user()->hasTeamPermission($team, TeamPermission::ManageOperations),
            ],
        ];
    }

    /**
     * Get the planner row for a load.
     *
     * @return array<string, mixed>
     */
    protected function summary(Load $load): array
    {
        return [
            'id' => $load->id,
            'number' => $load->number,
            'status' => $load->status->value,
            'status_label' => $load->status->label(),
            'notes' => $load->notes,
            'shipments_count' => (int) $load->shipments_count,
            'created_at' => $load->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @return array<string, mixed>
     */
    protected function detail(Load $load): array
    {
        $load->load(['shipments' => fn ($query) => $query->orderBy('id')]);

        return [
            ...$this->summary($load),
            'shipments_count' => $load->shipments->count(),
            'shipments' => $load->shipments
                ->map(fn (Shipment $shipment) => [
                    'id' => $shipment->id,
                    'number' => $shipment->number,
                    'status' => $shipment->status->value,
                    'status_label' => $shipment->status->label(),
                    'customer_name' => $shipment->customer_name,
                    'pieces' => $shipment->pieces,
                    'weight_grams' => $shipment->weight_grams,
                ])
                ->all(),
            'totals' => [
                'weight_grams' => $load->shipments->sum('weight_grams'),
                'volume_cm3' => $load->shipments->sum('volume_cm3'),
                'pieces' => $load->shipments->sum('pieces'),
            ],
        ];
    }
}
