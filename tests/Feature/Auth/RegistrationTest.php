<?php

use App\Enums\CompanyType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Models\Zone;

function registrationData(array $overrides = []): array
{
    return [
        'type' => 'postharvest',
        'company_name' => 'Poscosecha Las Rosas',
        'tax_id' => '1790012345001',
        'zone_id' => Zone::factory()->create()->id,
        'address_reference' => 'Junto a la iglesia',
        'name' => 'Ana Pérez',
        'phone' => '0991234567',
        'password' => 'clave-segura',
        // Obligatorio desde la semana 3: sin esto no se crea la cuenta.
        'terms' => '1',
        ...$overrides,
    ];
}

test('la pantalla de registro se muestra con el tipo elegido', function () {
    Zone::factory()->count(2)->create();

    $this->get(route('register', ['type' => 'grower']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/register')
            ->where('type', 'grower')
            ->has('zones', 2));
});

test('registra una empresa con su usuario dueño', function (string $type) {
    $this->post(route('register.store'), registrationData(['type' => $type]))
        ->assertRedirect(route('dashboard', absolute: false));

    $company = Company::sole();
    $user = User::sole();

    expect($company->type)->toBe(CompanyType::from($type))
        ->and($company->name)->toBe('Poscosecha Las Rosas')
        ->and($company->tax_id)->toBe('1790012345001')
        ->and($company->isVerified())->toBeFalse()
        ->and($user->company_id)->toBe($company->id)
        ->and($user->role)->toBe(UserRole::Owner)
        ->and($user->phone)->toBe('+593991234567');

    $this->assertAuthenticatedAs($user);
})->with(['postharvest', 'grower']);

test('normaliza el celular al registrar', function (string $phone) {
    $this->post(route('register.store'), registrationData(['phone' => $phone]));

    expect(User::sole()->phone)->toBe('+593991234567');
})->with(['0991234567', '991234567', '+593991234567', '099 123 4567']);

test('rechaza un celular que no es de Ecuador', function () {
    $this->post(route('register.store'), registrationData(['phone' => '022345678']))
        ->assertSessionHasErrors('phone');

    expect(Company::count())->toBe(0);
});

test('rechaza un celular que ya tiene cuenta, aunque esté escrito distinto', function () {
    User::factory()->create(['phone' => '+593991234567']);

    $this->post(route('register.store'), registrationData(['phone' => '991234567']))
        ->assertSessionHasErrors('phone');

    expect(Company::count())->toBe(1); // solo la empresa del usuario existente
});

test('valida RUC o cédula, sector y contraseña', function () {
    $this->post(route('register.store'), registrationData([
        'tax_id' => '12345',
        'zone_id' => 999,
        'password' => 'corta',
    ]))->assertSessionHasErrors(['tax_id', 'zone_id', 'password']);

    expect(User::count())->toBe(0);
});

test('no acepta sectores desactivados', function () {
    $zone = Zone::factory()->inactive()->create();

    $this->post(route('register.store'), registrationData(['zone_id' => $zone->id]))
        ->assertSessionHasErrors('zone_id');
});
