<?php

use App\Models\User;

test('registration marks the account verified when verification is disabled', function () {
    config(['auth.email_verification_required' => false]);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeTrue();
});

test('unverified users reach verified-only routes when verification is disabled', function () {
    config(['auth.email_verification_required' => false]);

    $user = User::factory()->unverified()->create();

    expect($user->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertOk();
});

test('verification is still enforced when required', function () {
    config(['auth.email_verification_required' => true]);

    $user = User::factory()->unverified()->create();

    expect($user->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertRedirect(route('verification.notice'));
});
