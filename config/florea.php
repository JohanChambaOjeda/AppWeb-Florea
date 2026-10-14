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

];
