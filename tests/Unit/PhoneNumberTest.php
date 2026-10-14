<?php

use App\Support\PhoneNumber;

it('normaliza celulares de Ecuador a E.164', function (string $input) {
    expect(PhoneNumber::normalize($input))->toBe('+593991234567');
})->with([
    '0991234567',
    '991234567',
    '+593991234567',
    '593991234567',
    '099 123 4567',
    '099-123-4567',
    '+593 99 123 4567',
]);

it('rechaza números que no son celulares de Ecuador', function (?string $input) {
    expect(PhoneNumber::normalize($input))->toBeNull();
})->with([
    null,
    '',
    '022345678',     // fijo de Quito
    '09912345',      // corto
    '09912345678',   // largo
    '0891234567',    // no empieza en 9
    '+573001234567', // Colombia
    'abc',
]);

it('formatea para mostrar y para WhatsApp', function () {
    expect(PhoneNumber::display('+593991234567'))->toBe('099 123 4567')
        ->and(PhoneNumber::whatsapp('+593991234567'))->toBe('593991234567');
});
