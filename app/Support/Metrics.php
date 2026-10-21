<?php

namespace App\Support;

use App\Models\Listing;
use Illuminate\Support\Carbon;

final class Metrics
{
    /**
     * Minutos promedio desde que se publica hasta que llega la PRIMERA oferta.
     *
     * Es la métrica clave del piloto: mide si Florea de verdad convierte días
     * de llamadas en horas. Solo cuenta las publicaciones que recibieron al
     * menos una oferta; las que nunca recibieron nada no tienen espera que
     * medir y hundirían el promedio con un valor inventado.
     *
     * La resta se hace en PHP a propósito: MySQL usa TIMESTAMPDIFF y SQLite
     * julianday, y los tests corren sobre SQLite. A escala de piloto son
     * decenas de filas.
     *
     * @return array{minutes: int|null, listings: int}
     */
    public static function timeToFirstOffer(?int $days = null): array
    {
        $listings = Listing::query()
            ->whereHas('offers')
            ->withMin('offers as first_offer_at', 'created_at')
            ->when($days !== null, fn ($q) => $q->where('listings.created_at', '>=', now()->subDays($days)))
            ->get(['id', 'created_at']);

        $waits = [];

        foreach ($listings as $listing) {
            if ($listing->first_offer_at === null) {
                continue;
            }

            $waits[] = $listing->created_at->diffInMinutes(
                Carbon::parse($listing->first_offer_at),
                absolute: true,
            );
        }

        if ($waits === []) {
            return ['minutes' => null, 'listings' => 0];
        }

        return [
            'minutes' => (int) round(array_sum($waits) / count($waits)),
            'listings' => count($waits),
        ];
    }
}
