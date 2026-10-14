<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('la página de contraseña se muestra', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('security.edit'))
        ->assertOk();
});

test('cambia la contraseña', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'nueva-clave',
            'password_confirmation' => 'nueva-clave',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check('nueva-clave', $user->refresh()->password))->toBeTrue();
});

test('pide la contraseña actual correcta', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'nueva-clave',
            'password_confirmation' => 'nueva-clave',
        ])
        ->assertSessionHasErrors('current_password');
});
