<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Le llega a un productor cuando alguien publica un "Busco" que incluye una
 * de sus variedades. Es la notificación que hace que la app sirva: el
 * productor no tiene que estar mirando la pantalla.
 *
 * Va encolada porque un "Busco" de ocho variedades puede tocar a decenas de
 * productores y quien publica no debe esperar por eso.
 */
class MatchingListingPublished extends Notification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    /**
     * @param  list<string>  $varieties  Las variedades suyas que piden.
     */
    public function __construct(
        private readonly Listing $listing,
        private readonly array $varieties,
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
        $names = implode(', ', $this->varieties);

        return [
            'type' => 'matching_listing',
            'listing_id' => $this->listing->id,
            'company_name' => $this->listing->company->name,
            'varieties' => $this->varieties,
            'title' => 'Están buscando tu flor',
            'body' => "{$this->listing->company->name} busca {$names}.",
        ];
    }
}
