<?php

namespace App\Enums;

enum Unit: string
{
    use HasValues;

    case Fb = 'fb';
    case Hb = 'hb';
    case Qb = 'qb';
    case Bunch = 'bunch';
    case Mesh = 'mesh';
    case Stem = 'stem';

    public function label(): string
    {
        return match ($this) {
            self::Fb => 'FB',
            self::Hb => 'HB',
            self::Qb => 'QB',
            self::Bunch => 'Bonche',
            self::Mesh => 'Malla',
            self::Stem => 'Tallo',
        };
    }
}
