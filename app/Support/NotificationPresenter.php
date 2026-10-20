<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Da forma a las notificaciones para la campana.
 *
 * Los datos guardados nunca traen celulares (ver las clases de Notifications),
 * así que aquí solo se eligen los campos y se arma el enlace.
 */
final class NotificationPresenter
{
    public const LATEST = 15;

    /**
     * @return list<array<string, mixed>>
     */
    public static function latestFor(User $user): array
    {
        return array_values(
            $user->notifications()
                ->latest()
                ->limit(self::LATEST)
                ->get()
                ->map(self::one(...))
                ->all()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function one(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;
        $listingId = $data['listing_id'] ?? null;

        return [
            'id' => $notification->id,
            'kind' => $data['type'] ?? 'unknown',
            'title' => $data['title'] ?? 'Novedad en Florea',
            'body' => $data['body'] ?? '',
            'listingId' => $listingId,
            'url' => $listingId !== null ? route('listings.show', $listingId) : null,
            'read' => $notification->read_at !== null,
            'createdAt' => $notification->created_at?->toIso8601String(),
        ];
    }
}
