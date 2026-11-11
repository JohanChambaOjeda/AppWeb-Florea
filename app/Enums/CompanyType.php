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

    /**
     * REGLA 8: el productor vende y la poscosecha compra. Nunca al revés.
     *
     * De aquí sale todo lo demás —qué publica cada tipo, qué ve en el inicio
     * y dónde puede ofertar—, así que la regla está escrita una sola vez. Si
     * algún día cambia el negocio, se cambia aquí y no en veinte sitios.
     */
    public function publishes(): ListingKind
    {
        return match ($this) {
            self::Postharvest => ListingKind::Want,
            self::Grower => ListingKind::Have,
        };
    }

    /**
     * Donde puede ofertar: en lo que publica el otro tipo.
     */
    public function offersOn(): ListingKind
    {
        return $this->other()->publishes();
    }

    public function other(): self
    {
        return match ($this) {
            self::Postharvest => self::Grower,
            self::Grower => self::Postharvest,
        };
    }

    /**
     * Para cuando alguien llega por URL a donde no le toca.
     */
    public function publishHint(): string
    {
        return match ($this) {
            self::Postharvest => 'Como poscosecha, aquí publicas la flor que te falta.',
            self::Grower => 'Como productor, aquí publicas la flor que tienes disponible.',
        };
    }
}
