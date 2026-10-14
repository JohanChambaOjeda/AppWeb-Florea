<?php

namespace App\Enums;

enum ListingKind: string
{
    use HasValues;

    case Want = 'want';
    case Have = 'have';

    public function label(): string
    {
        return match ($this) {
            self::Want => 'Busco',
            self::Have => 'Disponible',
        };
    }
}
