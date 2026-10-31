<?php

namespace App\Support;

/**
 * Una notificación que además se manda como aviso push.
 *
 * Notifier::send() lo comprueba: si la notificación implementa esto, encola
 * el push con el texto que devuelva toPush().
 */
interface Pushable
{
    public function toPush(): PushMessage;
}
