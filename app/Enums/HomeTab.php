<?php

namespace App\Enums;

enum HomeTab: string
{
    use HasValues;

    case ForMe = 'for-me';
    case Available = 'available';
    case Mine = 'mine';

    public function label(): string
    {
        return match ($this) {
            self::ForMe => 'Pedidos para mí',
            self::Available => 'Disponible hoy',
            self::Mine => 'Mis publicaciones',
        };
    }
}
