<?php

namespace App\Enums;

/**
 * Cuánta flor se rechazó, en rangos.
 *
 * En el campo nadie sabe el porcentaje exacto: sabe si le rechazaron "nada",
 * "poquito" o "bastante". Se guarda en deals.rejected_pct como el valor
 * representativo de cada rango, y se vuelve a leer con fromPercent().
 */
enum RejectedRange: string
{
    use HasValues;

    case None = 'none';
    case Under10 = 'under_10';
    case From10To25 = '10_to_25';
    case Over25 = 'over_25';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Nada, 0%',
            self::Under10 => 'Menos del 10%',
            self::From10To25 => 'Entre 10% y 25%',
            self::Over25 => 'Más del 25%',
        };
    }

    /**
     * El valor que se guarda en deals.rejected_pct.
     */
    public function percent(): float
    {
        return match ($this) {
            self::None => 0.0,
            self::Under10 => 5.0,
            self::From10To25 => 17.5,
            self::Over25 => 35.0,
        };
    }

    /**
     * De vuelta al rango desde el número guardado.
     */
    public static function fromPercent(?float $percent): ?self
    {
        return match (true) {
            $percent === null => null,
            $percent <= 0.0 => self::None,
            $percent < 10.0 => self::Under10,
            $percent <= 25.0 => self::From10To25,
            default => self::Over25,
        };
    }
}
