<?php

namespace App\Enums;

enum Destination: string
{
    use HasValues;

    case Usa = 'usa';
    case Russia = 'russia';
    case Europe = 'europe';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Usa => 'Estados Unidos',
            self::Russia => 'Rusia',
            self::Europe => 'Europa',
            self::Other => 'Otro',
        };
    }
}
