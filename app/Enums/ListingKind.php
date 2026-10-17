<?php

namespace App\Enums;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

enum ListingKind: string
{
    use HasValues;

    case Want = 'want';
    case Have = 'have';

    public function label(): string
    {
        return match ($this) {
            self::Want => 'Busco',
            self::Have => 'Disponible',
        };
    }

    /**
     * Texto del botón que abre el formulario.
     */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Want => 'Busco flor',
            self::Have => 'Tengo disponible',
        };
    }

    /**
     * Texto del botón para responder, visto desde la otra empresa.
     */
    public function offerLabel(): string
    {
        return match ($this) {
            self::Want => 'Ofertar',
            self::Have => 'Quiero comprar',
        };
    }

    /**
     * En un "Busco" quien oferta es el vendedor (foto y precio obligatorios);
     * en un "Disponible" quien oferta es el comprador.
     */
    public function offererSells(): bool
    {
        return $this === self::Want;
    }

    public function expiryHours(): int
    {
        return (int) config("florea.expiry_hours.{$this->value}");
    }

    public function expiresAt(): CarbonInterface
    {
        return Date::now()->addHours($this->expiryHours());
    }
}
