<?php

use App\Models\User;

test('la página de mis datos se muestra', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk();
});

test('actualiza nombre y celular, normalizando el celular', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nombre Nuevo',
            'phone' => '0987654321',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh())
        ->name->toBe('Nombre Nuevo')
        ->phone->toBe('+593987654321');
});

test('no permite usar el celular de otra persona', function () {
    User::factory()->create(['phone' => '+593987654321']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'phone' => '0987654321',
        ])
        ->assertSessionHasErrors('phone');
});
