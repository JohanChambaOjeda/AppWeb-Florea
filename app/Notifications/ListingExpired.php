<?php

namespace App\Notifications;

use App\Models\Listing;
use App\Support\Pushable;
use App\Support\PushMessage;
use Illuminate\Notifications\Notification;

/**
 * Le llega a quien publicó cuando se le vence la publicación sin cerrarla.
 */
class ListingExpired extends Notification implements Pushable
{
    public function __construct(private readonly Listing $listing) {}

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
        return [
            'type' => 'listing_expired',
            'listing_id' => $this->listing->id,
            'title' => 'Se venció tu publicación',
            'body' => "Tu {$this->listing->kind->label()} ya no recibe ofertas. Puedes duplicarla si todavía la necesitas.",
        ];
    }

    public function toPush(): PushMessage
    {
        return new PushMessage(
            title: 'Se venció tu publicación',
            body: 'Tu '.$this->listing->kind->label().' ya no recibe ofertas. Puedes duplicarla.',
            url: route('listings.show', $this->listing->id),
            tag: 'listing-'.$this->listing->id,
        );
    }
}
