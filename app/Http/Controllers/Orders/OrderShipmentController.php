<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\ConvertOrderToShipment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\ConvertOrderRequest;
use App\Models\Order;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class OrderShipmentController extends Controller
{
    /**
     * Create a shipment from the given order.
     */
    public function store(
        ConvertOrderRequest $request,
        Team $current_team,
        Order $order,
        ConvertOrderToShipment $convert,
    ): RedirectResponse {
        Gate::authorize('manageOperations', $current_team);

        $shipment = $convert->handle($current_team, $order, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $shipment->number])]);

        return to_route('shipments.show', [
            'current_team' => $current_team->slug,
            'shipment' => $shipment->id,
        ]);
    }
}
