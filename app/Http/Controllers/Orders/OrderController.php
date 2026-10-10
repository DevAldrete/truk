<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\SaveOrder;
use App\Enums\OrderStatus;
use App\Enums\PartyType;
use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\SaveOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display the order book.
     */
    public function index(Request $request, Team $current_team): Response
    {
        return Inertia::render('orders/Index', $this->pageProps($request, $current_team));
    }

    /**
     * Display the order book with the given order open.
     */
    public function show(Request $request, Team $current_team, Order $order): Response
    {
        return Inertia::render('orders/Index', [
            ...$this->pageProps($request, $current_team),
            'order' => $this->detail($order),
        ]);
    }

    /**
     * Store a newly created order.
     */
    public function store(SaveOrderRequest $request, Team $current_team, SaveOrder $saveOrder): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $order = $saveOrder->create($current_team, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $order->number])]);

        return to_route('orders.show', [
            'current_team' => $current_team->slug,
            'order' => $order->id,
        ]);
    }

    /**
     * Update the given order.
     */
    public function update(SaveOrderRequest $request, Team $current_team, Order $order, SaveOrder $saveOrder): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $data = $request->validated();

        if ($data['status'] !== $order->status->value) {
            $target = OrderStatus::from($data['status']);

            if (! $order->status->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => __('That status change is not allowed.'),
                ]);
            }
        }

        $saveOrder->update($current_team, $order, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was updated.', ['name' => $order->number])]);

        return back();
    }

    /**
     * Remove the given order.
     */
    public function destroy(Team $current_team, Order $order): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $order->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $order->number])]);

        return to_route('orders.index', ['current_team' => $current_team->slug]);
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
            'orders' => Order::query()
                ->with('customerParty:id,name')
                ->withCount(['items', 'shipments'])
                ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(number) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(customer_name) LIKE ?', ['%'.mb_strtolower($search).'%'])))
                ->when(
                    is_string($status) && in_array($status, OrderStatus::values(), true),
                    fn ($query) => $query->where('status', $status),
                )
                ->latest()
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Order $order) => $this->summary($order)),
            'filters' => [
                'search' => $search,
                'status' => is_string($status) && in_array($status, OrderStatus::values(), true) ? $status : null,
            ],
            'statuses' => OrderStatus::options(),
            'customers' => $team->parties()
                ->where('type', PartyType::Customer->value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($party) => ['value' => (string) $party->id, 'label' => $party->name])
                ->all(),
            'locations' => $team->locations()
                ->orderBy('name')
                ->get(['id', 'name', 'city'])
                ->map(fn ($location) => [
                    'value' => (string) $location->id,
                    'label' => $location->name.' · '.$location->city,
                ])
                ->all(),
            'can' => [
                'manage' => $request->user()->hasTeamPermission($team, TeamPermission::ManageOperations),
            ],
        ];
    }

    /**
     * Get the order book row for an order.
     *
     * @return array<string, mixed>
     */
    protected function summary(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'currency' => $order->currency,
            'customer_party_id' => $order->customer_party_id,
            'customer_name' => $order->customer_name,
            'customer_rfc' => $order->customer_rfc,
            'requested_pickup_at' => $order->requested_pickup_at?->toIso8601String(),
            'requested_delivery_at' => $order->requested_delivery_at?->toIso8601String(),
            'notes' => $order->notes,
            'items_count' => (int) $order->items_count,
            'shipments_count' => (int) $order->shipments_count,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get the full record shown in the detail panel.
     *
     * @return array<string, mixed>
     */
    protected function detail(Order $order): array
    {
        $order->load([
            'items' => fn ($query) => $query->orderBy('id'),
            'shipments' => fn ($query) => $query
                ->with('loadGroup:id,number')
                ->withSum(['deliveryAttemptLines as delivered_quantity' => fn ($query) => $query->where('success', true)], 'quantity')
                ->orderBy('id'),
        ]);

        return [
            ...$this->summary($order),
            'items_count' => $order->items->count(),
            'shipments_count' => $order->shipments->count(),
            'items' => $order->items
                ->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'weight_grams' => $item->weight_grams,
                    'weight_kg' => $item->weight_grams / 1000,
                    'volume_cm3' => $item->volume_cm3,
                    'volume_m3' => $item->volume_cm3 / 1000000,
                    'hazmat' => $item->hazmat,
                ])
                ->all(),
            'shipments' => $order->shipments
                ->map(fn (Shipment $shipment) => [
                    'id' => $shipment->id,
                    'number' => $shipment->number,
                    'status' => $shipment->status->value,
                    'status_label' => $shipment->status->label(),
                    'pieces' => $shipment->pieces,
                    'load_id' => $shipment->load_id,
                    'load_number' => $shipment->loadGroup?->number,
                    'delivered_quantity' => (int) $shipment->getAttribute('delivered_quantity'),
                    'remaining_quantity' => max((int) $shipment->pieces - (int) $shipment->getAttribute('delivered_quantity'), 0),
                ])
                ->all(),
            'totals' => [
                'weight_grams' => $order->items->sum('weight_grams'),
                'volume_cm3' => $order->items->sum('volume_cm3'),
                'pieces' => $order->items->sum('quantity'),
                'hazmat' => $order->items->contains('hazmat', true),
            ],
        ];
    }
}
