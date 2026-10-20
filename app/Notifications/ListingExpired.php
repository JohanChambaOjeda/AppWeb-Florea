<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Notifications\Notification;

/**
 * Le llega a quien publicó cuando se le vence la publicación sin cerrarla.
 */
class ListingExpired extends Notification
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
}
