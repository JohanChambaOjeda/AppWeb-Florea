<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
|
| En Hostinger compartido no se puede dejar un proceso corriendo, así que
| todo cuelga de una sola línea de cron cada minuto:
|
|   * * * * * cd /home/USUARIO/florea && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Regla 7: vencer publicaciones y rechazar sus ofertas pendientes.
Schedule::command('listings:expire')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// La cola sin proceso permanente: cada minuto se vacía lo que haya y el
// comando termina solo. --stop-when-empty es justo para este caso.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

// Agrupa en un solo aviso los pedidos que llegaron seguidos, para no
// vibrarle el celular seis veces seguidas a un productor.
Schedule::command('push:digest')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Los trabajos fallidos no se acumulan para siempre.
Schedule::command('queue:prune-failed --hours=168')->weekly();
