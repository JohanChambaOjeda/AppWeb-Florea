<?php

namespace App\Enums;

enum CompanyType: string
{
    use HasValues;

    case Postharvest = 'postharvest';
    case Grower = 'grower';

    public function label(): string
    {
        return match ($this) {
            self::Postharvest => 'Poscosecha',
            self::Grower => 'Productor',
        };
    }
}
