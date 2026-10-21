<?php

use App\Models\Company;
use App\Models\CompanyVariety;
use App\Models\User;
use App\Models\Variety;

// growerUser() vive en tests/Pest.php. Sin argumentos devuelve un productor
// sin variedades, que es justo lo que necesitan estos tests de onboarding.

test('un productor sin variedades ve primero "Mis variedades"', function () {
    $user = growerUser();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('my-varieties.edit'));

    $this->get(route('my-varieties.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('my-varieties/edit')
            ->where('onboarding', true)
            ->where('standardLengths', [40, 50, 60, 70, 80, 90]));
});

test('un productor con variedades entra directo a la app', function () {
    $user = growerUser();
    CompanyVariety::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('la pantalla lista solo variedades activas', function () {
    Variety::factory()->count(3)->create();
    Variety::factory()->inactive()->create();

    $this->actingAs(growerUser())
        ->get(route('my-varieties.edit'))
        ->assertInertia(fn ($page) => $page->has('varieties', 3));
});

test('guarda variedades y largos, incluido un largo especial de 120', function () {
    $user = growerUser();
    [$freedom, $explorer] = Variety::factory()->count(2)->create();

    $this->actingAs($user)
        ->put(route('my-varieties.update'), [
            'varieties' => [
                ['variety_id' => $freedom->id, 'lengths' => [60, 50, 120]],
                ['variety_id' => $explorer->id, 'lengths' => [70]],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard')); // primera vez: sigue a la app

    $saved = $user->company->varieties()->get()->keyBy('id');

    expect($saved)->toHaveCount(2)
        ->and($saved[$freedom->id]->pivot->lengths)->toBe([50, 60, 120])
        ->and($saved[$explorer->id]->pivot->lengths)->toBe([70]);
});

test('editar reemplaza la selección anterior', function () {
    $user = growerUser();
    [$a, $b] = Variety::factory()->count(2)->create();
    $user->company->varieties()->attach($a->id, ['lengths' => [50]]);

    $this->actingAs($user)
        ->put(route('my-varieties.update'), [
            'varieties' => [['variety_id' => $b->id, 'lengths' => [60, 70]]],
        ])
        ->assertRedirect(route('my-varieties.edit'));

    expect($user->company->varieties()->pluck('varieties.id')->all())->toBe([$b->id]);
});

test('no borra en silencio una variedad que el admin desactivó', function () {
    $user = growerUser();
    $inactive = Variety::factory()->create();
    $active = Variety::factory()->create();
    $user->company->varieties()->attach($inactive->id, ['lengths' => [60]]);
    $inactive->update(['is_active' => false]);

    $this->actingAs($user)->put(route('my-varieties.update'), [
        'varieties' => [['variety_id' => $active->id, 'lengths' => [50]]],
    ]);

    expect($user->company->varieties()->pluck('varieties.id')->sort()->values()->all())
        ->toBe(collect([$inactive->id, $active->id])->sort()->values()->all());
});

test('valida variedades y largos', function (array $payload, string $errorKey) {
    $variety = Variety::factory()->create();
    $payload = json_decode(str_replace('__ID__', (string) $variety->id, json_encode($payload)), true);

    $this->actingAs(growerUser())
        ->put(route('my-varieties.update'), $payload)
        ->assertSessionHasErrors($errorKey);
})->with([
    'sin variedades' => [['varieties' => []], 'varieties'],
    'sin largos' => [['varieties' => [['variety_id' => '__ID__', 'lengths' => []]]], 'varieties.0.lengths'],
    'largo muy corto' => [['varieties' => [['variety_id' => '__ID__', 'lengths' => [20]]]], 'varieties.0.lengths.0'],
    'largo muy largo' => [['varieties' => [['variety_id' => '__ID__', 'lengths' => [151]]]], 'varieties.0.lengths.0'],
    'variedad inexistente' => [['varieties' => [['variety_id' => 999999, 'lengths' => [60]]]], 'varieties.0.variety_id'],
]);

test('no acepta variedades desactivadas', function () {
    $variety = Variety::factory()->inactive()->create();

    $this->actingAs(growerUser())
        ->put(route('my-varieties.update'), [
            'varieties' => [['variety_id' => $variety->id, 'lengths' => [60]]],
        ])
        ->assertSessionHasErrors('varieties.0.variety_id');
});

test('una poscosecha no tiene "Mis variedades"', function () {
    $user = User::factory()->create(['company_id' => Company::factory()->postharvest()->verified()]);

    $this->actingAs($user)->get(route('my-varieties.edit'))->assertForbidden();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('un productor no verificado va a "cuenta en revisión"', function () {
    $user = User::factory()->create(['company_id' => Company::factory()->grower()]);

    $this->actingAs($user)->get(route('my-varieties.edit'))->assertRedirect(route('account.pending'));
});

test('un productor puede sembrar dos variedades en el mismo largo', function () {
    $user = growerUser();
    $freedom = Variety::factory()->create(['name' => 'Freedom']);
    $explorer = Variety::factory()->create(['name' => 'Explorer']);

    // Casi todo se siembra en 60: si esto falla, la pantalla es inusable.
    $this->actingAs($user)
        ->put(route('my-varieties.update'), [
            'varieties' => [
                ['variety_id' => $freedom->id, 'lengths' => [60]],
                ['variety_id' => $explorer->id, 'lengths' => [60, 70]],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($user->company->companyVarieties()->count())->toBe(2);
});
