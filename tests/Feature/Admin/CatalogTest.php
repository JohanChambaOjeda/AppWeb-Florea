<?php

use App\Models\CompanyVariety;
use App\Models\User;
use App\Models\Variety;
use App\Models\Zone;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('crea y edita una variedad', function () {
    $this->post(route('admin.varieties.store'), ['name' => 'Momentum', 'color' => 'Rojo'])
        ->assertSessionHasNoErrors();

    $variety = Variety::where('name', 'Momentum')->sole();
    expect($variety->color)->toBe('Rojo')->and($variety->is_active)->toBeTrue();

    $this->put(route('admin.varieties.update', $variety), ['name' => 'Momentum Red', 'color' => null])
        ->assertSessionHasNoErrors();

    expect($variety->refresh())->name->toBe('Momentum Red')->color->toBeNull();
});

test('no permite variedades repetidas', function () {
    Variety::factory()->create(['name' => 'Freedom']);

    $this->post(route('admin.varieties.store'), ['name' => 'Freedom'])
        ->assertSessionHasErrors('name');
});

test('activa y desactiva una variedad', function () {
    $variety = Variety::factory()->create();

    $this->post(route('admin.varieties.toggle', $variety));
    expect($variety->refresh()->is_active)->toBeFalse();

    $this->post(route('admin.varieties.toggle', $variety));
    expect($variety->refresh()->is_active)->toBeTrue();
});

test('borra una variedad que no se usa', function () {
    $variety = Variety::factory()->create();

    $this->delete(route('admin.varieties.destroy', $variety));

    expect(Variety::find($variety->id))->toBeNull();
});

test('no borra una variedad que ya se usa', function () {
    $variety = CompanyVariety::factory()->create()->variety;

    $this->delete(route('admin.varieties.destroy', $variety));

    expect(Variety::find($variety->id))->not->toBeNull();
});

test('crea, edita y desactiva un sector', function () {
    $this->post(route('admin.zones.store'), ['name' => 'Cajas'])
        ->assertSessionHasNoErrors();

    $zone = Zone::where('name', 'Cajas')->sole();
    expect($zone->canton)->toBe('Pedro Moncayo');

    $this->put(route('admin.zones.update', $zone), ['name' => 'Cajas Alto', 'canton' => 'Cayambe'])
        ->assertSessionHasNoErrors();
    expect($zone->refresh())->name->toBe('Cajas Alto')->canton->toBe('Cayambe');

    $this->post(route('admin.zones.toggle', $zone));
    expect($zone->refresh()->is_active)->toBeFalse();
});

test('no permite sectores repetidos en el mismo cantón', function () {
    Zone::factory()->create(['name' => 'Tabacundo']);

    $this->post(route('admin.zones.store'), ['name' => 'Tabacundo'])
        ->assertSessionHasErrors('name');
});
