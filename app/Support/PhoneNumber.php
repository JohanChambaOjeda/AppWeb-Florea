<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normaliza un celular de Ecuador a E.164 (+5939XXXXXXXX).
     *
     * Acepta 0991234567, 991234567, 593991234567 o +593991234567, con o sin
     * espacios, guiones o paréntesis. Devuelve null si no es un celular válido.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if (str_starts_with($digits, '593')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^9\d{8}$/', $digits)) {
            return null;
        }

        return '+593'.$digits;
    }

    public static function isValid(?string $value): bool
    {
        return self::normalize($value) !== null;
    }

    /**
     * Formato local para mostrar: 099 123 4567.
     */
    public static function display(string $e164): string
    {
        $local = '0'.substr($e164, 4);

        return substr($local, 0, 3).' '.substr($local, 3, 3).' '.substr($local, 6);
    }

    /**
     * Número para enlaces wa.me (sin el +).
     */
    public static function whatsapp(string $e164): string
    {
        return ltrim($e164, '+');
    }
}
