<?php

use App\Concerns\BelongsToTeam;

test('every model is scoped to a team unless it is tenant independent', function () {
    $tenantIndependent = [
        'Membership',
        'Team',
        'TeamInvitation',
        'User',
        // Global, versioned reference catalogs: never tenant data.
        'CatalogVersion',
        'SatProductServiceCode',
        'SatUnitCode',
        'SatPostalCode',
        'TollBooth',
    ];

    $models = collect(glob(app_path('Models/*.php')))
        ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
        ->reject(fn (string $model) => in_array($model, $tenantIndependent, true));

    $models->each(function (string $model) {
        $class = "App\\Models\\{$model}";

        expect(in_array(BelongsToTeam::class, class_uses_recursive($class), true))
            ->toBeTrue("[{$class}] must use the BelongsToTeam trait, or be listed as tenant independent.");
    });
});
