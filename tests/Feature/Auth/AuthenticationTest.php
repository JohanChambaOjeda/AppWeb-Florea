<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('la pantalla de ingreso se muestra', function () {
    $this->get(route('login'))->assertOk();
});

test('ingresa con el celular en cualquier formato', function (string $phone) {
    $user = User::factory()->create(['phone' => '+593991234567']);

    $this->post(route('login.store'), [
        'phone' => $phone,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
})->with([
    'con 0' => '0991234567',
    'sin 0' => '991234567',
    'E.164' => '+593991234567',
    'con espacios' => '099 123 4567',
]);

test('no ingresa con una contraseña incorrecta', function () {
    User::factory()->create(['phone' => '+593991234567']);

    $this->post(route('login.store'), [
        'phone' => '0991234567',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('phone');

    $this->assertGuest();
});

test('no ingresa con un celular que no existe', function () {
    $this->post(route('login.store'), [
        'phone' => '0999999999',
        'password' => 'password',
    ])->assertSessionHasErrors('phone');

    $this->assertGuest();
});

test('cierra sesión', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
});

test('limita los intentos de ingreso por celular, sin importar el formato', function () {
    User::factory()->create(['phone' => '+593991234567']);

    RateLimiter::increment(md5('login'.'+593991234567|127.0.0.1'), amount: 5);

    $this->post(route('login.store'), [
        'phone' => '0991234567',
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

test('olvidé mi contraseña muestra el enlace de WhatsApp del admin', function () {
    config(['florea.admin_phone' => '0991112233']);

    $this->get(route('password.request'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/forgot-password')
            ->where('whatsappUrl', fn (string $url) => str_starts_with($url, 'https://wa.me/593991112233?text=')));
});
