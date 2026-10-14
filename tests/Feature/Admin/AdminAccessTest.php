<?php

use App\Models\Company;
use App\Models\Listing;
use App\Models\User;

test('el admin entra al panel', function (string $route) {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route($route))
        ->assertOk();
})->with(['admin.dashboard', 'admin.companies.index', 'admin.varieties.index', 'admin.zones.index']);

test('un usuario normal no entra a /admin', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertForbidden();
})->with(['admin.dashboard', 'admin.companies.index', 'admin.varieties.index', 'admin.zones.index']);

test('un visitante va a la pantalla de ingreso', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('el admin que entra a /dashboard va a su panel', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

test('el inicio del admin muestra los contadores', function () {
    Company::factory()->count(2)->create(); // por verificar
    Company::factory()->postharvest()->verified()->create();
    Company::factory()->grower()->verified()->count(3)->create();
    Company::factory()->create()->reject('Datos falsos'); // rechazada: no cuenta
    Listing::factory()->create();
    Listing::factory()->create(['expires_at' => now()->subDay()]); // vencida: no cuenta

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->where('stats.pending', 2)
            // Las publicaciones de la factory crean empresas verificadas extra.
            ->where('stats.verifiedPostharvest', Company::verified()->where('type', 'postharvest')->count())
            ->where('stats.verifiedGrowers', Company::verified()->where('type', 'grower')->count())
            ->where('stats.openListings', 1));
});
