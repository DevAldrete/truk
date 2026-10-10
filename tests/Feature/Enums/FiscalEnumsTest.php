<?php

use App\Enums\BillingStatus;
use App\Enums\CfdiType;
use App\Enums\DocumentStatus;
use App\Enums\ExpenseType;
use App\Enums\SettlementStatus;

test('the fiscal and billing enums are labelled in both locales', function () {
    $enums = [
        CfdiType::class,
        DocumentStatus::class,
        BillingStatus::class,
        SettlementStatus::class,
    ];

    foreach ($enums as $enum) {
        foreach ($enum::cases() as $case) {
            foreach (['es', 'en'] as $locale) {
                app()->setLocale($locale);

                expect($case->label())->not->toBe('', "{$enum}::{$case->name} is missing a {$locale} label.");
            }
        }
    }
});

test('the trip-cost expense types are labelled', function () {
    $types = [ExpenseType::Viaticos, ExpenseType::Maniobras, ExpenseType::Fines, ExpenseType::Detention];

    foreach ($types as $type) {
        foreach (['es', 'en'] as $locale) {
            app()->setLocale($locale);

            expect($type->label())->not->toBe('');
        }
    }
});
