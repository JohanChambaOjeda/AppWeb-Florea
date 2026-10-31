<?php

namespace App\Support;

/**
 * El texto de un aviso push.
 *
 * Corto y concreto: en la pantalla de bloqueo de un celular caben unos 40
 * caracteres de título y dos líneas de cuerpo. "Nueva oferta: 3 HB Explorer
 * 70 a $0,45" dice qué pasó sin tener que abrir nada.
 */
final class PushMessage
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        /** A dónde lleva al tocarlo. */
        public readonly ?string $url = null,
        /** Avisos con la misma etiqueta se reemplazan en vez de apilarse. */
        public readonly ?string $tag = null,
    ) {}
}
