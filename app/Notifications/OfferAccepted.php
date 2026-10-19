<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Notifications\Notification;

/**
 * Le llega a quien ofertó cuando le aceptan la oferta.
 *
 * No guarda el celular: el contacto se lee en la pantalla, que lo arma
 * OfferPresenter comprobando que el trato exista.
 */
class OfferAccepted extends Notification
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
            'type' => 'offer_accepted',
            'listing_id' => $listing->id,
            'offer_id' => $this->offer->id,
            'company_name' => $listing->company->name,
            'title' => '¡Te aceptaron la oferta!',
            'body' => "{$listing->company->name} aceptó tu oferta. Ya puedes ver su celular y coordinar la entrega.",
        ];
    }
}
