<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

/**
 * Guarda la foto del día de una oferta.
 *
 * Llegan desde la cámara del celular, así que pesan varios MB. Se bajan a
 * 1600 px de lado y a JPEG calidad 70 antes de tocar el disco: una foto de
 * 4 MB queda en unos 200 KB, que es lo que aguanta el hosting compartido y
 * lo que carga rápido con datos móviles en el campo.
 */
final class PhotoStorage
{
    public const DISK = 'public';

    public const MAX_SIDE = 1600;

    public const QUALITY = 70;

    /**
     * Devuelve la ruta relativa dentro del disco public.
     */
    public static function store(UploadedFile $file): string
    {
        $image = ImageManager::gd()->read($file->getRealPath());

        // Las fotos de celular vienen con la rotación en los metadatos EXIF;
        // sin esto se guardan de lado.
        $image->orient();
        $image->scaleDown(self::MAX_SIDE, self::MAX_SIDE);

        $path = 'photos/'.now()->format('Y/m').'/'.Str::uuid()->toString().'.jpg';

        Storage::disk(self::DISK)->put($path, (string) $image->toJpeg(self::QUALITY));

        return $path;
    }

    public static function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }

    public static function url(string $path): string
    {
        return Storage::disk(self::DISK)->url($path);
    }
}
