<?php

use App\Enums\Locale;
use App\Enums\TeamRole;

test('a role is labelled in the active locale', function () {
    app()->setLocale(Locale::Es->value);

    expect(TeamRole::Dispatcher->label())->toBe('Despachador');
});

test('every role is labelled in every supported locale', function () {
    foreach (Locale::cases() as $locale) {
        app()->setLocale($locale->value);

        foreach (TeamRole::cases() as $role) {
            expect($role->label())->not->toBe("roles.{$role->value}");
        }
    }
});

test('the owner outranks every other role', function () {
    foreach (TeamRole::cases() as $role) {
        if ($role === TeamRole::Owner) {
            continue;
        }

        expect(TeamRole::Owner->isAtLeast($role))->toBeTrue();
        expect($role->isAtLeast(TeamRole::Owner))->toBeFalse();
    }
});

test('the owner is not assignable to a member', function () {
    $values = array_column(TeamRole::assignable(), 'value');

    expect($values)
        ->not->toContain(TeamRole::Owner->value)
        ->toContain(TeamRole::Driver->value);
});
