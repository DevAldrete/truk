<?php

namespace App\Http\Controllers\Shipments;

use App\Actions\Shipments\StorePackages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shipments\StorePackageRequest;
use App\Models\Package;
use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ShipmentPackageController extends Controller
{
    /**
     * Add packages to the given shipment.
     */
    public function store(
        StorePackageRequest $request,
        Team $current_team,
        Shipment $shipment,
        StorePackages $storePackages,
    ): RedirectResponse {
        Gate::authorize('manageOperations', $current_team);

        $count = $storePackages->handle(
            $current_team,
            $shipment,
            $request->integer('count'),
            $request->filled('weight_grams') ? (int) $request->input('weight_grams') : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count package was added.|:count packages were added.', $count, ['count' => $count])]);

        return back();
    }

    /**
     * Remove the given package from the shipment.
     */
    public function destroy(Team $current_team, Shipment $shipment, Package $package): RedirectResponse
    {
        Gate::authorize('manageOperations', $current_team);

        $package->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $package->code])]);

        return back();
    }
}
