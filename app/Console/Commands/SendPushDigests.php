<?php

namespace App\Console\Commands;

use App\Support\GrowerPush;
use Illuminate\Console\Command;

/**
 * Manda el resumen agrupado a los productores cuya ventana ya se cerró:
 * "Hay 4 pedidos nuevos con tus variedades".
 *
 * Corre cada 5 minutos desde el scheduler. El primer aviso de cada tanda ya
 * salió al instante; esto recoge los que llegaron detrás.
 */
class SendPushDigests extends Command
{
    protected $signature = 'push:digest';

    protected $description = 'Agrupa en un solo aviso los pedidos que llegaron seguidos';

    public function handle(): int
    {
        $sent = GrowerPush::sendDigests();

        $this->info("Resúmenes enviados: {$sent}.");

        return self::SUCCESS;
    }
}
