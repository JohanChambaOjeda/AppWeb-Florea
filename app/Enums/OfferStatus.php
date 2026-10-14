<?php

namespace App\Enums;

enum OfferStatus: string
{
    use HasValues;

    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviada',
            self::Accepted => 'Aceptada',
            self::Rejected => 'Rechazada',
            self::Withdrawn => 'Retirada',
        };
    }
}
