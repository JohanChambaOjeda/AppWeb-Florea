<?php

namespace App\Console\Commands;

use GdImage;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Dibuja los íconos de la PWA con GD.
 *
 * Es provisional: una flor de ocho pétalos en violeta con el centro celeste,
 * los colores de Florea. Se dibuja con código en vez de rasterizar el SVG
 * porque Hostinger compartido no trae Imagick y GD no lee SVG.
 *
 * Cuando llegue el logo definitivo, reemplaza los PNG de public/icons a mano
 * y olvídate de este comando (o ajusta los colores de abajo).
 */
class MakeIcons extends Command
{
    protected $signature = 'florea:icons';

    protected $description = 'Genera los íconos de la PWA en public/icons';

    private const VIOLET = [124, 58, 237];   // violet-600

    private const SKY = [14, 165, 233];      // sky-500

    public function handle(): int
    {
        $dir = public_path('icons');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // El maskable deja 20% de margen: Android recorta el ícono en
        // círculo, rombo o cuadrado según el launcher, y sin ese margen
        // le corta los pétalos.
        /** @var array<string, array{int<1, max>, float, bool}> $files */
        $files = [
            'icon-192.png' => [192, 0.80, false],
            'icon-512.png' => [512, 0.80, false],
            'icon-512-maskable.png' => [512, 0.58, false],
            'apple-touch-icon.png' => [180, 0.80, true],
        ];

        foreach ($files as $name => [$size, $scale, $isApple]) {
            $image = $this->draw($size, $scale);

            imagepng($image, $dir.'/'.$name, 9);
            imagedestroy($image);

            $this->line("  {$name} ({$size}×{$size})");
        }

        // iOS busca apple-touch-icon.png en la raíz del sitio.
        copy($dir.'/apple-touch-icon.png', public_path('apple-touch-icon.png'));

        $this->info('Íconos generados en public/icons.');

        return self::SUCCESS;
    }

    /**
     * Fondo violeta a sangre y la flor centrada ocupando $scale del lienzo.
     *
     * @param  int<1, max>  $size
     */
    private function draw(int $size, float $scale): GdImage
    {
        $image = imagecreatetruecolor($size, $size);

        imageantialias($image, true);
        imagefill($image, 0, 0, $this->color($image, self::VIOLET));

        $center = $size / 2;
        $radius = $size * $scale / 2;

        $white = $this->color($image, [255, 255, 255], 25);
        $whiteBack = $this->color($image, [255, 255, 255], 70);

        // Ocho pétalos: cuatro en diagonal al fondo y cuatro al frente.
        // Se dibujan primero los de atrás para que los de delante tapen.
        foreach ([[45, $whiteBack, 0.19, 0.40], [0, $white, 0.23, 0.46]] as [$offset, $color, $wide, $long]) {
            for ($i = 0; $i < 4; $i++) {
                $this->petal(
                    $image,
                    $center,
                    $radius,
                    deg2rad($offset + $i * 90),
                    $wide,
                    $long,
                    $color,
                );
            }
        }

        imagefilledellipse(
            $image,
            (int) $center,
            (int) $center,
            (int) ($radius * 0.5),
            (int) ($radius * 0.5),
            $this->color($image, self::SKY),
        );

        return $image;
    }

    /**
     * Un pétalo: una elipse desplazada del centro y girada $angle radianes.
     */
    private function petal(
        GdImage $image,
        float $center,
        float $radius,
        float $angle,
        float $wide,
        float $long,
        int $color,
    ): void {
        $a = $radius * $wide;   // mitad del ancho del pétalo
        $b = $radius * $long;   // mitad del largo del pétalo
        $points = [];

        // Elipse centrada en (0, -b): el borde interior toca el centro de la
        // flor y el exterior llega a -2b. Así los pétalos salen del centro
        // en vez de flotar sueltos.
        for ($t = 0; $t < 48; $t++) {
            $u = $t / 48 * 2 * M_PI;
            $x = $a * cos($u);
            $y = -$b + $b * sin($u);

            $points[] = $center + $x * cos($angle) - $y * sin($angle);
            $points[] = $center + $x * sin($angle) + $y * cos($angle);
        }

        imagefilledpolygon($image, array_map(intval(...), $points), $color);
    }

    /**
     * Reserva un color. GD devuelve false si no puede, cosa que en una
     * imagen truecolor no debería pasar nunca; si pasa, mejor enterarse.
     *
     * @param  array{int<0, 255>, int<0, 255>, int<0, 255>}  $rgb
     * @param  int<0, 127>  $alpha  0 opaco, 127 transparente
     */
    private function color(GdImage $image, array $rgb, int $alpha = 0): int
    {
        $color = imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], $alpha);

        if ($color === false) {
            throw new RuntimeException('GD no pudo reservar el color del ícono.');
        }

        return $color;
    }
}
