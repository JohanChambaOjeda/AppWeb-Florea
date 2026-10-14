<?php

use App\Models\User;

test('los visitantes van a la pantalla de ingreso', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('un usuario de empresa verificada entra a la app', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

test('un usuario de empresa no verificada va a "cuenta en revisión"', function (string $route) {
    $user = User::factory()->unverifiedCompany()->create();

    $this->actingAs($user)->get(route($route))->assertRedirect(route('account.pending'));
})->with(['dashboard', 'profile.edit', 'security.edit']);

test('la pantalla de cuenta en revisión se muestra al no verificado', function () {
    $user = User::factory()->unverifiedCompany()->create();

    $this->actingAs($user)
        ->get(route('account.pending'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('account/pending')
            ->where('companyName', $user->company->name));
});

test('un usuario verificado no se queda en "cuenta en revisión"', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('account.pending'))
        ->assertRedirect(route('dashboard'));
});

test('el admin no pasa por la verificación de empresa', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('profile.edit'))
        ->assertOk();
});
