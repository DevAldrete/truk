<?php

namespace App\Http\Controllers\Driver;

use App\Actions\Execution\RecordScan;
use App\Http\Controllers\Concerns\AuthorizesTripExecution;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\RecordScanRequest;
use App\Models\Package;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ScanController extends Controller
{
    use AuthorizesTripExecution;

    /**
     * Record a package custody scan on the trip.
     */
    public function store(
        RecordScanRequest $request,
        Team $current_team,
        Trip $trip,
        RecordScan $record,
    ): RedirectResponse {
        $this->authorizeTripExecution($request, $current_team, $trip);

        $package = $current_team->packages()->findOrFail($request->integer('package_id'));

        $onTrip = $trip->stops()
            ->whereHas('shipments', fn ($query) => $query->whereKey($package->shipment_id))
            ->exists();

        if (! $onTrip) {
            throw ValidationException::withMessages([
                'package_id' => __('The package is not on this trip.'),
            ]);
        }

        $record->handle($current_team, [
            ...$request->validated(),
            'trip_id' => $trip->id,
            'shipment_id' => $package->shipment_id,
        ], $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Scan recorded.')]);

        return back();
    }
}
