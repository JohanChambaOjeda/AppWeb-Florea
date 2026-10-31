<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Support\OfferPresenter;
use App\Support\Pushable;
use App\Support\PushMessage;
use Illuminate\Notifications\Notification;

/**
 * Le llega a quien publicó cuando recibe una oferta nueva.
 *
 * Sin cola: escribir una fila es barato y el aviso debe aparecer al instante.
 * El que sí va encolado es el aviso masivo a productores del paso 5.
 *
 * Nunca guarda celulares: esto se lee desde la campana, no desde el trato.
 */
class OfferReceived extends Notification implements Pushable
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

    /**
     * "Nueva oferta: 3 HB Explorer 70 a $0,45"
     */
    public function toPush(): PushMessage
    {
        $what = OfferPresenter::summary($this->offer);
        $price = $this->offer->price_per_stem !== null
            ? ' a $'.number_format((float) $this->offer->price_per_stem, 2, ',', '')
            : '';

        return new PushMessage(
            title: 'Nueva oferta',
            body: $what.$price,
            url: route('listings.show', $this->offer->listing_id),
            tag: 'listing-'.$this->offer->listing_id,
        );
    }
}
