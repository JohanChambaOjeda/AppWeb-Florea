<?php

namespace App\Enums;

enum DeliveryResult: string
{
    use HasValues;

    case Yes = 'yes';
    case No = 'no';
    case Partial = 'partial';

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Sí',
            self::No => 'No',
            self::Partial => 'Parcial',
        };
    }
}
