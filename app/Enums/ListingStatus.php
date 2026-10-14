<?php

namespace App\Enums;

enum ListingStatus: string
{
    use HasValues;

    case Open = 'open';
    case Closed = 'closed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::Closed => 'Cerrada',
            self::Expired => 'Vencida',
        };
    }
}
