<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dispatch\DispatchController;
use App\Http\Controllers\Driver\DeliveryAttemptController;
use App\Http\Controllers\Driver\ExpenseController;
use App\Http\Controllers\Driver\IncidentController as DriverIncidentController;
use App\Http\Controllers\Driver\PodEvidenceController;
use App\Http\Controllers\Driver\ProofOfDeliveryController;
use App\Http\Controllers\Driver\ScanController;
use App\Http\Controllers\Fleet\DriverController;
use App\Http\Controllers\Fleet\DriverDocumentController;
use App\Http\Controllers\Fleet\TrailerController;
use App\Http\Controllers\Fleet\TrailerDocumentController;
use App\Http\Controllers\Fleet\VehicleController;
use App\Http\Controllers\Fleet\VehicleDocumentController;
use App\Http\Controllers\Incidents\IncidentController;
use App\Http\Controllers\Loads\LoadController;
use App\Http\Controllers\Loads\LoadShipmentController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Locations\LocationController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Orders\OrderShipmentController;
use App\Http\Controllers\Parties\PartyContactController;
use App\Http\Controllers\Parties\PartyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Shipments\ShipmentController;
use App\Http\Controllers\Shipments\ShipmentPackageController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Trips\StopController;
use App\Http\Controllers\Trips\StopReorderController;
use App\Http\Controllers\Trips\StopShipmentController;
use App\Http\Controllers\Trips\TripController;
use App\Http\Controllers\Trips\TripDispatchController;
use App\Http\Controllers\Trips\TripResourceController;
use App\Http\Controllers\Trips\TripShipmentController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::post('locale', LocaleController::class)->name('locale.update');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('dispatch', DispatchController::class)->name('dispatch');

        Route::get('search', SearchController::class)->name('search');

        Route::get('parties', [PartyController::class, 'index'])->name('parties.index');
        Route::post('parties', [PartyController::class, 'store'])->name('parties.store');
        Route::get('parties/{party}', [PartyController::class, 'show'])->name('parties.show');
        Route::patch('parties/{party}', [PartyController::class, 'update'])->name('parties.update');
        Route::delete('parties/{party}', [PartyController::class, 'destroy'])->name('parties.destroy');

        Route::scopeBindings()->group(function () {
            Route::post('parties/{party}/contacts', [PartyContactController::class, 'store'])->name('parties.contacts.store');
            Route::patch('parties/{party}/contacts/{contact}', [PartyContactController::class, 'update'])->name('parties.contacts.update');
            Route::delete('parties/{party}/contacts/{contact}', [PartyContactController::class, 'destroy'])->name('parties.contacts.destroy');
        });

        Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
        Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');
        Route::patch('locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

        Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::post('drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::get('drivers/{driver}', [DriverController::class, 'show'])->name('drivers.show');
        Route::patch('drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::delete('drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');

        Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::post('vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
        Route::patch('vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

        Route::get('trailers', [TrailerController::class, 'index'])->name('trailers.index');
        Route::post('trailers', [TrailerController::class, 'store'])->name('trailers.store');
        Route::get('trailers/{trailer}', [TrailerController::class, 'show'])->name('trailers.show');
        Route::patch('trailers/{trailer}', [TrailerController::class, 'update'])->name('trailers.update');
        Route::delete('trailers/{trailer}', [TrailerController::class, 'destroy'])->name('trailers.destroy');

        Route::scopeBindings()->group(function () {
            Route::post('drivers/{driver}/documents', [DriverDocumentController::class, 'store'])->name('drivers.documents.store');
            Route::patch('drivers/{driver}/documents/{document}', [DriverDocumentController::class, 'update'])->name('drivers.documents.update');
            Route::delete('drivers/{driver}/documents/{document}', [DriverDocumentController::class, 'destroy'])->name('drivers.documents.destroy');

            Route::post('vehicles/{vehicle}/documents', [VehicleDocumentController::class, 'store'])->name('vehicles.documents.store');
            Route::patch('vehicles/{vehicle}/documents/{document}', [VehicleDocumentController::class, 'update'])->name('vehicles.documents.update');
            Route::delete('vehicles/{vehicle}/documents/{document}', [VehicleDocumentController::class, 'destroy'])->name('vehicles.documents.destroy');

            Route::post('trailers/{trailer}/documents', [TrailerDocumentController::class, 'store'])->name('trailers.documents.store');
            Route::patch('trailers/{trailer}/documents/{document}', [TrailerDocumentController::class, 'update'])->name('trailers.documents.update');
            Route::delete('trailers/{trailer}/documents/{document}', [TrailerDocumentController::class, 'destroy'])->name('trailers.documents.destroy');
        });

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        Route::post('orders/{order}/shipments', [OrderShipmentController::class, 'store'])->name('orders.shipments.store');

        Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
        Route::patch('shipments/{shipment}', [ShipmentController::class, 'update'])->name('shipments.update');
        Route::delete('shipments/{shipment}', [ShipmentController::class, 'destroy'])->name('shipments.destroy');

        Route::scopeBindings()->group(function () {
            Route::post('shipments/{shipment}/packages', [ShipmentPackageController::class, 'store'])->name('shipments.packages.store');
            Route::delete('shipments/{shipment}/packages/{package}', [ShipmentPackageController::class, 'destroy'])->name('shipments.packages.destroy');
        });

        Route::get('loads', [LoadController::class, 'index'])->name('loads.index');
        Route::post('loads', [LoadController::class, 'store'])->name('loads.store');
        Route::get('loads/{load}', [LoadController::class, 'show'])->name('loads.show');
        Route::patch('loads/{load}', [LoadController::class, 'update'])->name('loads.update');
        Route::delete('loads/{load}', [LoadController::class, 'destroy'])->name('loads.destroy');

        Route::scopeBindings()->group(function () {
            Route::post('loads/{load}/shipments', [LoadShipmentController::class, 'store'])->name('loads.shipments.store');
            Route::delete('loads/{load}/shipments/{shipment}', [LoadShipmentController::class, 'destroy'])->name('loads.shipments.destroy');
        });

        Route::get('trips', [TripController::class, 'index'])->name('trips.index');
        Route::post('trips', [TripController::class, 'store'])->name('trips.store');
        Route::get('trips/{trip}', [TripController::class, 'show'])->name('trips.show');
        Route::patch('trips/{trip}', [TripController::class, 'update'])->name('trips.update');
        Route::delete('trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
        Route::put('trips/{trip}/resources', [TripResourceController::class, 'update'])->name('trips.resources.update');
        Route::post('trips/{trip}/shipments', [TripShipmentController::class, 'store'])->name('trips.shipments.store');
        Route::delete('trips/{trip}/shipments/{shipment}', [TripShipmentController::class, 'destroy'])->name('trips.shipments.destroy');
        Route::post('trips/{trip}/dispatch', [TripDispatchController::class, 'store'])->name('trips.dispatch');

        Route::patch('incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');

        Route::scopeBindings()->group(function () {
            Route::post('trips/{trip}/stops', [StopController::class, 'store'])->name('trips.stops.store');
            Route::put('trips/{trip}/stops/reorder', [StopReorderController::class, 'update'])->name('trips.stops.reorder');
            Route::patch('trips/{trip}/stops/{stop}', [StopController::class, 'update'])->name('trips.stops.update');
            Route::delete('trips/{trip}/stops/{stop}', [StopController::class, 'destroy'])->name('trips.stops.destroy');
            Route::post('trips/{trip}/stops/{stop}/shipments', [StopShipmentController::class, 'store'])->name('trips.stops.shipments.store');
            Route::delete('trips/{trip}/stops/{stop}/shipments/{shipment}', [StopShipmentController::class, 'destroy'])->name('trips.stops.shipments.destroy');
        });

        // Driver execution portal: a driver runs the trip assigned to them.
        Route::prefix('driver')->name('driver.')->group(function () {
            Route::scopeBindings()->group(function () {
                Route::post('trips/{trip}/stops/{stop}/attempts', [DeliveryAttemptController::class, 'store'])
                    ->name('trips.stops.attempts.store');
                Route::post('trips/{trip}/stops/{stop}/pod', [ProofOfDeliveryController::class, 'store'])
                    ->name('trips.stops.pod.store');
            });

            Route::post('trips/{trip}/scans', [ScanController::class, 'store'])->name('trips.scans.store');

            Route::post('trips/{trip}/incidents', [DriverIncidentController::class, 'store'])->name('trips.incidents.store');

            Route::post('trips/{trip}/expenses', [ExpenseController::class, 'store'])->name('trips.expenses.store');

            Route::get('pods/{pod}/signature', [PodEvidenceController::class, 'signature'])->name('pods.signature');
            Route::get('pods/{pod}/photos/{index}', [PodEvidenceController::class, 'photo'])->name('pods.photos.show');
            Route::get('pods/{pod}/documents/{index}', [PodEvidenceController::class, 'document'])->name('pods.documents.show');
        });
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
