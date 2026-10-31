<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anota cuándo entró cada usuario por última vez.
 *
 * La columna existe desde la semana 1 pero nadie la escribía, así que la
 * métrica de "empresas activas en la semana" habría salido siempre en cero.
 *
 * Se actualiza como mucho cada 15 minutos para no meter un UPDATE en cada
 * petición: para medir actividad semanal esa precisión sobra.
 */
class TrackLastSeen
{
    private const EVERY_MINUTES = 15;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $stale = $user->last_seen_at === null
                || $user->last_seen_at->diffInMinutes(now()) >= self::EVERY_MINUTES;

            if ($stale) {
                $user->forceFill(['last_seen_at' => now()])->saveQuietly();
            }
        }

        return $next($request);
    }
}
