<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Largos de tallo (cm)
    |--------------------------------------------------------------------------
    |
    | Los formularios ofrecen los largos estándar como botones, más un campo
    | "Otro largo" que acepta un entero dentro del rango custom_length
    | (para casos como 100 o 120 cm).
    |
    */

    'standard_lengths' => [40, 50, 60, 70, 80, 90],

    'custom_length' => [
        'min' => 30,
        'max' => 150,
    ],

    /*
    |--------------------------------------------------------------------------
    | Administrador
    |--------------------------------------------------------------------------
    |
    | Lo usa el AdminSeeder para crear la cuenta admin, y el enlace de
    | WhatsApp de "Olvidé mi contraseña".
    |
    */

    'admin_phone' => env('ADMIN_PHONE'),

    'admin_password' => env('ADMIN_PASSWORD'),

];
