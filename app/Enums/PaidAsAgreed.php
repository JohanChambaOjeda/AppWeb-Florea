<?php

namespace App\Enums;

/**
 * Lo que responde el productor cuando califica a la poscosecha: si le pagó
 * como habían acordado.
 *
 * "Todavía no" existe porque un plazo a 30 días no es un incumplimiento el
 * día 3. Esa respuesta NO cuenta en el porcentaje de la poscosecha: ni a
 * favor ni en contra. Contar un pago pendiente como impago castigaría a
 * quien respeta el plazo largo que se acordó.
 */
enum PaidAsAgreed: string
{
    use HasValues;

    case Yes = 'yes';
    case No = 'no';
    case NotYet = 'not_yet';

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Sí, como acordamos',
            self::No => 'No',
            self::NotYet => 'Todavía no',
        };
    }

    /**
     * Si esta respuesta entra en el porcentaje de "pagos como se acordó".
     */
    public function counts(): bool
    {
        return $this !== self::NotYet;
    }
}
