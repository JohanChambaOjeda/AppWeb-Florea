<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Cuándo paga la poscosecha.
 *
 * Se acuerda dentro de la app y queda registrado en el trato. Antes esto se
 * hablaba por WhatsApp y acababa en "yo dije 15 días" contra "tú dijiste
 * contra entrega": al quedar escrito, la pregunta de si pagó como acordaron
 * tiene una respuesta comprobable y la calificación deja de ser una opinión.
 *
 * Lo declara siempre la poscosecha, que es quien paga (regla 8): al ofertar
 * si el "Disponible" es del productor, o al aceptar si el "Busco" es suyo.
 * Así el productor lo ve siempre antes de que el trato se cierre.
 */
enum PaymentTerm: string
{
    use HasValues;

    case OnDelivery = 'on_delivery';
    case Days8 = 'days_8';
    case Days15 = 'days_15';
    case Days30 = 'days_30';

    public function label(): string
    {
        return match ($this) {
            self::OnDelivery => 'Contra entrega',
            self::Days8 => 'A 8 días',
            self::Days15 => 'A 15 días',
            self::Days30 => 'A 30 días',
        };
    }

    public function days(): int
    {
        return match ($this) {
            self::OnDelivery => 0,
            self::Days8 => 8,
            self::Days15 => 15,
            self::Days30 => 30,
        };
    }

    /**
     * Cuándo vence el pago de un trato cerrado en esa fecha.
     */
    public function dueAfter(CarbonInterface $closedAt): CarbonInterface
    {
        return $closedAt->copy()->addDays($this->days());
    }

    /**
     * Si el plazo ya pasó. Sirve para no contar como incumplimiento un pago
     * a 30 días cuando solo han pasado 3.
     */
    public function isDue(CarbonInterface $closedAt): bool
    {
        return $this->dueAfter($closedAt)->isPast();
    }
}
