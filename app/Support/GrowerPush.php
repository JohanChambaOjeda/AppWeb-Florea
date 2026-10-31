<?php

namespace App\Support;

use App\Models\Listing;
use App\Models\User;
use App\Notifications\WebPushNotification;
use Illuminate\Support\Facades\DB;

/**
 * Los avisos push de "están buscando tu flor", con el freno anti-spam.
 *
 * La regla: el primero sale al instante, porque un pedido para hoy no puede
 * esperar. Los que lleguen en los siguientes minutos se agrupan en uno solo,
 * porque en una mañana movida pueden entrar seis pedidos y nadie quiere seis
 * vibraciones seguidas.
 */
final class GrowerPush
{
    /** Minutos que dura la ventana de agrupación. */
    public const WINDOW_MINUTES = 15;

    /**
     * Encola el aviso de una publicación para un productor.
     *
     * Devuelve true si salió al instante, false si quedó para el resumen.
     */
    public static function queue(User $user, Listing $listing): bool
    {
        // El índice único hace de guardia: una publicación, un aviso.
        $isNew = DB::table('pending_push_listings')->insertOrIgnore([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'created_at' => now(),
        ]);

        if ($isNew === 0) {
            return false;
        }

        $windowOpen = $user->push_window_until !== null
            && $user->push_window_until->isFuture();

        if ($windowOpen) {
            return false;
        }

        // Sale ya, y se abre la ventana para los que vengan detrás.
        self::flushFor($user, [$listing->id]);

        $user->forceFill([
            'push_window_until' => now()->addMinutes(self::WINDOW_MINUTES),
        ])->save();

        return true;
    }

    /**
     * Manda el resumen a los productores cuya ventana ya se cerró.
     *
     * Lo llama el comando push:digest desde el scheduler.
     */
    public static function sendDigests(): int
    {
        $sent = 0;

        $pending = DB::table('pending_push_listings')
            ->select('user_id')
            ->groupBy('user_id')
            ->pluck('user_id');

        foreach (User::whereIn('id', $pending)->get() as $user) {
            // Si la ventana sigue abierta, todavía pueden llegar más.
            if ($user->push_window_until !== null && $user->push_window_until->isFuture()) {
                continue;
            }

            $ids = array_values(array_map(
                intval(...),
                DB::table('pending_push_listings')
                    ->where('user_id', $user->id)
                    ->pluck('listing_id')
                    ->all(),
            ));

            if ($ids === []) {
                continue;
            }

            self::flushFor($user, $ids);

            // Cerrada la tanda: el siguiente pedido vuelve a ser instantáneo.
            $user->forceFill(['push_window_until' => null])->save();

            $sent++;
        }

        return $sent;
    }

    /**
     * Manda el aviso de esas publicaciones y las saca de la cola.
     *
     * @param  list<int>  $listingIds
     */
    private static function flushFor(User $user, array $listingIds): void
    {
        $count = count($listingIds);

        $user->notify(new WebPushNotification(
            $count === 1
                ? self::single((int) $listingIds[0])
                : self::grouped($count),
        ));

        DB::table('pending_push_listings')
            ->where('user_id', $user->id)
            ->whereIn('listing_id', $listingIds)
            ->delete();
    }

    /**
     * "Buscan Explorer 70 y 3 variedades más — para hoy"
     */
    private static function single(int $listingId): PushMessage
    {
        $listing = Listing::with('items.variety')->find($listingId);

        if ($listing === null) {
            return new PushMessage(
                title: 'Están buscando tu flor',
                body: 'Toca para ver el pedido.',
                url: route('dashboard'),
            );
        }

        $first = $listing->items->first();
        $name = $first?->variety->name ?? 'flor';
        $lengths = $first !== null ? implode('/', $first->lengths) : '';
        $rest = $listing->items->count() - 1;

        $what = trim("{$name} {$lengths}");

        if ($rest > 0) {
            $what .= $rest === 1 ? ' y 1 variedad más' : " y {$rest} variedades más";
        }

        $when = match (true) {
            $listing->needed_by?->isToday() === true => ' — para hoy',
            $listing->needed_by?->isTomorrow() === true => ' — para mañana',
            default => '',
        };

        return new PushMessage(
            title: 'Buscan tu flor',
            body: $what.$when,
            url: route('listings.show', $listing->id),
            tag: 'listing-'.$listing->id,
        );
    }

    private static function grouped(int $count): PushMessage
    {
        return new PushMessage(
            title: 'Están buscando tu flor',
            body: "Hay {$count} pedidos nuevos con tus variedades",
            url: route('dashboard'),
            tag: 'matching-digest',
        );
    }
}
