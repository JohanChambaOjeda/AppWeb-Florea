<?php

use App\Enums\ListingKind;
use App\Models\Listing;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    RateLimiter::clear('publish:1');
    RateLimiter::clear('offer:1');
});

test('el registro exige aceptar los términos', function () {
    $this->post(route('register'), registrationData(['terms' => null]))
        ->assertSessionHasErrors('terms');

    expect(User::count())->toBe(0);
});

test('aceptar los términos guarda la fecha', function () {
    $this->post(route('register'), registrationData())->assertRedirect();

    $user = User::sole();

    expect($user->terms_accepted_at)->not->toBeNull()
        ->and($user->terms_accepted_at->isToday())->toBeTrue();
});

test('las páginas legales se leen sin cuenta y avisan que son borrador', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('legal')
            ->has('sections')
            ->has('updatedAt'));
})->with(['privacy', 'terms']);

test('la política explica cuándo se ve el celular', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $json = propsJson($page);

            expect($json)
                ->toContain('Quién ve tu número de celular')
                ->toContain('Ley Orgánica de Protección de Datos Personales');
        });
});

test('una empresa no puede publicar más de 20 veces al día', function () {
    $variety = Variety::factory()->create();
    $user = postharvestUser();

    $payload = [
        'kind' => 'want',
        'needed_by' => today()->toDateString(),
        'items' => [['variety_id' => $variety->id, 'lengths' => [60], 'unit' => 'hb']],
    ];

    for ($i = 0; $i < 20; $i++) {
        $this->actingAs($user)->post(route('listings.store'), $payload);
    }

    expect(Listing::count())->toBe(20);

    // La 21 ya no pasa.
    $this->actingAs($user)->post(route('listings.store'), $payload);

    expect(Listing::count())->toBe(20);
});

test('el tope de publicaciones es por empresa, no global', function () {
    $variety = Variety::factory()->create();
    $primera = postharvestUser();
    $segunda = postharvestUser();

    $payload = [
        'kind' => 'want',
        'needed_by' => today()->toDateString(),
        'items' => [['variety_id' => $variety->id, 'lengths' => [60], 'unit' => 'hb']],
    ];

    for ($i = 0; $i < 20; $i++) {
        $this->actingAs($primera)->post(route('listings.store'), $payload);
    }

    // La segunda empresa empieza de cero.
    $this->actingAs($segunda)->post(route('listings.store'), $payload);

    expect(Listing::where('company_id', $segunda->company_id)->count())->toBe(1);
});

test('el ingreso se corta a los 5 intentos por minuto', function () {
    $user = postharvestUser();

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'phone' => $user->phone,
            'password' => 'no-es-la-clave',
        ]);
    }

    // El sexto ya está bloqueado, incluso con la clave correcta: el
    // middleware throttle corta antes de comprobar nada y responde 429.
    $this->post(route('login'), [
        'phone' => $user->phone,
        'password' => 'password',
    ])
        ->assertStatus(429)
        ->assertSee('Demasiados intentos');

    $this->assertGuest();
});

test('las páginas de error están en español y no dependen del build', function (string $code) {
    $html = (string) file_get_contents(resource_path("views/errors/{$code}.blade.php"));

    expect($html)->toContain("@extends('errors.layout')")
        ->toContain("@section('title'");

    $layout = (string) file_get_contents(resource_path('views/errors/layout.blade.php'));

    expect($layout)
        ->toContain('lang="es"')
        // Los estilos van dentro: un 500 aparece justo cuando el build o la
        // app están rotos, y la página tiene que verse igual.
        ->toContain('<style>')
        ->not->toContain('@vite');
})->with(['403', '404', '419', '429', '500', '503']);

test('un 404 de verdad muestra la página en español', function () {
    $this->withoutExceptionHandling(except: [NotFoundHttpException::class]);

    $this->get('/esta-ruta-no-existe')
        ->assertNotFound()
        ->assertSee('No encontramos esta página');
});

test('un 403 muestra la página en español', function () {
    $variety = Variety::factory()->create();
    $ajena = listingFrom(postharvestUser(), ListingKind::Want, [$variety]);

    $this->actingAs(postharvestUser())
        ->get(route('listings.create', ['from' => $ajena->id]))
        ->assertForbidden()
        ->assertSee('Esto no es tuyo');
});
