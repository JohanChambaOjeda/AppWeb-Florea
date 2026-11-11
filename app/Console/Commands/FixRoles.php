<?php

namespace App\Console\Commands;

use App\Enums\ListingKind;
use App\Enums\ListingStatus;
use App\Enums\OfferStatus;
use App\Models\Listing;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Limpia las publicaciones de antes de la regla 8.
 *
 * Hasta la semana 4 cualquier empresa podía publicar de los dos tipos, así
 * que hay "Busco" de productores y "Disponible" de poscosechas. Las consultas
 * del inicio ya no los muestran, pero siguen vivos: aceptan ofertas por URL
 * directa, cuentan en las métricas y vencen solos dentro de días.
 *
 * Este comando los cierra y rechaza sus ofertas pendientes. Enseña la lista
 * antes de tocar nada, porque cerrar una publicación no se deshace.
 */
class FixRoles extends Command
{
    protected $signature = 'florea:fix-roles {--apply : Cerrar de verdad, en vez de solo listar}';

    protected $description = 'Encuentra las publicaciones que rompen la regla de roles y las cierra';

    public function handle(): int
    {
        $listings = $this->offending()->with(['company', 'user'])->get();

        if ($listings->isEmpty()) {
            $this->info('No hay publicaciones que rompan la regla. Nada que hacer.');

            return self::SUCCESS;
        }

        $this->warn("Publicaciones que rompen la regla: {$listings->count()}");
        $this->newLine();

        $this->table(
            ['ID', 'Empresa', 'Tipo de empresa', 'Publicó', 'Debería publicar', 'Estado', 'Ofertas pendientes'],
            $listings->map(fn (Listing $l) => [
                $l->id,
                $l->company->name,
                $l->company->type->label(),
                $l->kind->label(),
                $l->company->type->publishes()->label(),
                $l->status->value,
                $l->offers()->where('status', OfferStatus::Sent)->count(),
            ])->all(),
        );

        if (! $this->option('apply')) {
            $this->newLine();
            $this->line('Esto fue solo la lista. Para cerrarlas de verdad:');
            $this->line('  php artisan florea:fix-roles --apply');

            return self::SUCCESS;
        }

        if (! $this->confirm('¿Cerrar estas publicaciones y rechazar sus ofertas pendientes?', false)) {
            $this->info('Cancelado. No se tocó nada.');

            return self::SUCCESS;
        }

        [$closed, $rejected] = $this->close($listings);

        $this->newLine();
        $this->info("Publicaciones cerradas: {$closed}. Ofertas rechazadas: {$rejected}.");

        return self::SUCCESS;
    }

    /**
     * Una publicación rompe la regla cuando su tipo no es el que publica el
     * tipo de su empresa. Solo importan las abiertas: las ya cerradas o
     * vencidas no hacen daño y son parte del historial.
     *
     * @return Builder<Listing>
     */
    private function offending(): Builder
    {
        return Listing::query()
            ->where('status', ListingStatus::Open)
            ->where(function (Builder $q) {
                // Un OR por cada tipo de publicación, leyendo del enum quién
                // debería haberla publicado. Sin SQL crudo: así vale igual en
                // el SQLite de los tests y en el MariaDB de producción.
                foreach (ListingKind::cases() as $kind) {
                    $q->orWhere(fn (Builder $w) => $w
                        ->where('kind', $kind)
                        ->whereHas('company', fn (Builder $c) => $c->where('type', '!=', $kind->publishedBy())));
                }
            });
    }

    /**
     * @param  Collection<int, Listing>  $listings
     * @return array{int, int}
     */
    private function close($listings): array
    {
        $closed = 0;
        $rejected = 0;

        DB::transaction(function () use ($listings, &$closed, &$rejected) {
            foreach ($listings as $listing) {
                $rejected += $listing->offers()
                    ->where('status', OfferStatus::Sent)
                    ->update(['status' => OfferStatus::Rejected]);

                $listing->update(['status' => ListingStatus::Closed]);
                $closed++;
            }
        });

        return [$closed, $rejected];
    }
}
