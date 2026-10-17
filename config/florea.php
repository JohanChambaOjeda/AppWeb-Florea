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
    | Publicaciones
    |--------------------------------------------------------------------------
    |
    | Horas de vida de cada tipo desde que se crea. Un "Busco" dura dos días
    | porque el pedido se arma con calma; un "Disponible" dura uno, porque la
    | flor ya cortada no espera.
    |
    | needed_by_days: hasta cuántos días adelante se puede pedir la flor.
    | notes_max: largo máximo de la nota libre.
    |
    */

    'expiry_hours' => [
        'want' => 48,
        'have' => 24,
    ],

    'needed_by_days' => 30,

    'notes_max' => 300,

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
