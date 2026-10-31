<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Enums\OfferStatus;
use App\Models\Listing;
use App\Notifications\ListingExpired;
use App\Notifications\OfferRejected;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Marca como vencidas las publicaciones cuyo plazo ya pasó y rechaza las
 * ofertas que seguían esperando respuesta (regla 7).
 *
 * Corre cada 5 minutos desde el scheduler. Es idempotente: si corre dos veces
 * la segunda no encuentra nada, porque solo mira las que siguen abiertas.
 */
class ExpireListings extends Command
{
    protected $signature = 'listings:expire';

    protected $description = 'Vence las publicaciones pasadas de plazo y rechaza sus ofertas pendientes';

    public function handle(): int
    {
        $expired = 0;
        $rejected = 0;

        Listing::overdue()
            ->with(['user', 'offers' => fn ($q) => $q->where('status', OfferStatus::Sent)->with('user')])
            ->chunkById(100, function ($listings) use (&$expired, &$rejected) {
                foreach ($listings as $listing) {
                    $pending = $listing->offers;

                    DB::transaction(function () use ($listing) {
                        $listing->update(['status' => ListingStatus::Expired]);

                        $listing->offers()
                            ->where('status', OfferStatus::Sent)
                            ->update(['status' => OfferStatus::Rejected]);
                    });

                    Notifier::send($listing->user, new ListingExpired($listing));

                    foreach ($pending as $offer) {
                        Notifier::send($offer->user, new OfferRejected($offer, becauseListingClosed: true));
                        $rejected++;
                    }

                    $expired++;
                }
            });

        $this->info("Publicaciones vencidas: {$expired}. Ofertas rechazadas: {$rejected}.");

        return self::SUCCESS;
    }
}
