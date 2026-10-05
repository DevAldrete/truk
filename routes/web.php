<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Locations\LocationController;
use App\Http\Controllers\Parties\PartyContactController;
use App\Http\Controllers\Parties\PartyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::post('locale', LocaleController::class)->name('locale.update');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

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
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
