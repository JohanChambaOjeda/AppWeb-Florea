<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Notifications\Notification;

/**
 * Le llega a quien publicó cuando recibe una oferta nueva.
 *
 * Sin cola: escribir una fila es barato y el aviso debe aparecer al instante.
 * El que sí va encolado es el aviso masivo a productores del paso 5.
 *
 * Nunca guarda celulares: esto se lee desde la campana, no desde el trato.
 */
class OfferReceived extends Notification
{
    public function __construct(private readonly Offer $offer) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $listing = $this->offer->listing;

        return [
            'type' => 'offer_received',
            'listing_id' => $listing->id,
            'offer_id' => $this->offer->id,
            'company_name' => $this->offer->company->name,
            'title' => 'Tienes una oferta nueva',
            'body' => "{$this->offer->company->name} ofertó en tu {$listing->kind->label()}.",
        ];
    }
}
