<?php

namespace App\Enums;

enum Delivery: string
{
    use HasValues;

    case IDeliver = 'i_deliver';
    case Pickup = 'pickup';
    case ToAgree = 'to_agree';

    public function label(): string
    {
        return match ($this) {
            self::IDeliver => 'La llevo yo',
            self::Pickup => 'Pasar retirando',
            self::ToAgree => 'A convenir',
        };
    }
}
