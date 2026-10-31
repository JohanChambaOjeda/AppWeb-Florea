<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\WebPushNotification;
use Illuminate\Notifications\Notification;

/**
 * Avisa por los dos caminos a la vez: la campana y el push.
 *
 * Existe para que el texto del aviso viva en UN solo sitio (la clase de la
 * notificación) y para que nadie se olvide del push al agregar un evento
 * nuevo: basta con que la notificación implemente Pushable.
 */
final class Notifier
{
    public static function send(User $user, Notification $notification): void
    {
        // La campana, al instante: es solo una fila.
        $user->notify($notification);

        // El push, por la cola.
        if ($notification instanceof Pushable) {
            $user->notify(new WebPushNotification($notification->toPush()));
        }
    }
}
