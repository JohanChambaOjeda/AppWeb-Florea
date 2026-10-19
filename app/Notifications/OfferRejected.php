<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Notifications\Notification;

/**
 * Le llega a quien ofertó cuando le rechazan la oferta, o cuando quien
 * publicó cerró con "Ya completé" y las ofertas que quedaban se rechazan.
 */
class OfferRejected extends Notification
{
    public function __construct(
        private readonly Offer $offer,
        private readonly bool $becauseListingClosed = false,
    ) {}

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
            'type' => 'offer_rejected',
            'listing_id' => $listing->id,
            'offer_id' => $this->offer->id,
            'company_name' => $listing->company->name,
            'title' => 'Tu oferta no quedó',
            'body' => $this->becauseListingClosed
                ? "{$listing->company->name} ya completó su pedido."
                : "{$listing->company->name} no tomó tu oferta esta vez.",
        ];
    }
}
