<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\Deal;
use App\Support\Pushable;
use App\Support\PushMessage;
use Illuminate\Notifications\Notification;

/**
 * "¿Cómo te fue con [empresa]?"
 *
 * Llega al día siguiente del trato y otra vez a los 3 días si no respondió.
 * Más de dos veces sería acoso.
 */
class RatingRequested extends Notification implements Pushable
{
    public function __construct(
        private readonly Deal $deal,
        private readonly Company $other,
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
        return [
            'type' => 'rating_requested',
            'deal_id' => $this->deal->id,
            'listing_id' => $this->deal->offer->listing_id,
            'company_name' => $this->other->name,
            'title' => "¿Cómo te fue con {$this->other->name}?",
            'body' => 'Califica el trato para que los demás sepan con quién tratan.',
        ];
    }

    public function toPush(): PushMessage
    {
        return new PushMessage(
            title: "¿Cómo te fue con {$this->other->name}?",
            body: 'Toca para calificar el trato',
            url: route('ratings.create', $this->deal->id),
            tag: 'rating-'.$this->deal->id,
        );
    }
}
