<?php

namespace App\Enums;

enum Plan: string
{
    use HasValues;

    case Free = 'free';
    case Pilot = 'pilot';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Gratis',
            self::Pilot => 'Piloto',
            self::Paid => 'Pagado',
        };
    }
}
