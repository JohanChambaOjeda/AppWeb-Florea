<?php

namespace App\Enums;

enum CutPoint: string
{
    use HasValues;

    case Closed = 'closed';
    case Normal = 'normal';
    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::Closed => 'Cerrado',
            self::Normal => 'Normal',
            self::Open => 'Abierto',
        };
    }
}
