<?php

namespace App\Notifications;

use App\Support\PushMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Manda un aviso push, y nada más.
 *
 * Va aparte de las notificaciones de la campana a propósito: la campana se
 * escribe al instante (es una fila en la base) y el push sale por la cola,
 * porque hablar con el servidor de Google o Apple puede tardar segundos y
 * nadie debería esperar eso mientras oferta.
 *
 * Quien lo dispara es Notifier::send(), no cada controlador.
 */
class WebPushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PushMessage $message) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        $push = (new WebPushMessage)
            ->title($this->message->title)
            ->body($this->message->body)
            ->icon('/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            // El celular vibra: en el invernadero no se oye el sonido.
            ->vibrate([200, 100, 200])
            ->data(['url' => $this->message->url]);

        if ($this->message->tag !== null) {
            $push->tag($this->message->tag);
        }

        return $push;
    }
}
