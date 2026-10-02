<?php

use App\Enums\Locale;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the saved preference wins over the browser language', function () {
    $user = User::factory()->create(['locale' => Locale::En->value]);

    $response = $this->actingAs($user)
        ->withHeader('Accept-Language', 'es-MX,es;q=0.9')
        ->get(route('dashboard', $user->currentTeam));

    $response->assertInertia(fn (Assert $page) => $page->where('locale', Locale::En->value));
});

test('the browser language is used when no preference is saved', function () {
    $response = $this->withHeader('Accept-Language', 'es-MX,es;q=0.9')->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('locale', Locale::Es->value));
});

test('the application default is used when the browser sends no language', function () {
    $response = $this->withHeader('Accept-Language', '')->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page->where('locale', config('app.locale')));
});

test('a guest can switch the locale for the session', function () {
    $response = $this->from(route('home'))
        ->withHeader('Accept-Language', 'es-MX,es;q=0.9')
        ->post(route('locale.update'), ['locale' => Locale::En->value]);

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('locale', Locale::En->value);

    $this->withHeader('Accept-Language', 'es-MX,es;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', Locale::En->value));
});

test('the locale chosen for the session wins over the saved preference', function () {
    $user = User::factory()->create(['locale' => Locale::En->value]);

    $this->actingAs($user)->post(route('locale.update'), ['locale' => Locale::Es->value]);

    $this->actingAs($user)
        ->get(route('dashboard', $user->currentTeam))
        ->assertInertia(fn (Assert $page) => $page->where('locale', Locale::Es->value));
});

test('switching the locale saves it on the authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('home'))
        ->post(route('locale.update'), ['locale' => Locale::En->value]);

    $response->assertRedirect(route('home'));

    expect($user->fresh()->locale)->toBe(Locale::En->value);
});

test('an unsupported locale is rejected', function () {
    $response = $this->from(route('home'))
        ->withHeader('Accept-Language', 'es-MX,es;q=0.9')
        ->post(route('locale.update'), ['locale' => 'fr']);

    $response->assertSessionHasErrors('locale');

    $this->withHeader('Accept-Language', 'es-MX,es;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', Locale::Es->value));
});

test('the interface strings for the active locale are shared', function () {
    $this->get(route('home')); // Establish a session.

    $this->post(route('locale.update'), ['locale' => Locale::Es->value]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', Locale::Es->value)
            ->where('translations.Log out', 'Cerrar sesión'));
});

test('validation messages are returned in the active locale', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('Accept-Language', 'es-MX,es;q=0.9')
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), ['name' => '', 'email' => 'not-an-email']);

    $response->assertSessionHasErrors([
        'name' => 'El campo nombre es obligatorio.',
        'email' => 'El campo correo electrónico debe ser un correo electrónico válido.',
    ]);
});
