<?php

namespace App\Jobs;

use App\Enums\CompanyType;
use App\Enums\ListingKind;
use App\Models\Company;
use App\Models\Listing;
use App\Models\Variety;
use App\Notifications\MatchingListingPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Avisa a los productores que siembran alguna de las variedades de un "Busco"
 * recién publicado.
 *
 * Usa la MISMA regla que la pestaña "Pedidos para mí": coincide por variedad,
 * no por largo. Si la regla cambia, cambia en los dos sitios.
 */
class NotifyMatchingGrowers implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Listing $listing) {}

    public function handle(): void
    {
        // Solo los "Busco": nadie quiere un aviso por cada sobrante ajeno.
        if ($this->listing->kind !== ListingKind::Want) {
            return;
        }

        $varietyIds = $this->listing->items()->pluck('variety_id')->unique();

        if ($varietyIds->isEmpty()) {
            return;
        }

        $companies = Company::query()
            ->verified()
            ->where('type', CompanyType::Grower)
            ->where('id', '!=', $this->listing->company_id)
            ->whereHas(
                'companyVarieties',
                fn (Builder $q) => $q->whereIn('variety_id', $varietyIds),
            )
            ->with([
                'users',
                // Solo las variedades que coinciden: el aviso las nombra.
                'varieties' => fn ($q) => $q->whereIn('varieties.id', $varietyIds),
            ])
            ->get();

        foreach ($companies as $company) {
            $names = array_values(
                $company->varieties
                    ->map(fn (Variety $variety) => $variety->name)
                    ->sort()
                    ->all()
            );

            foreach ($company->users as $user) {
                $user->notify(new MatchingListingPublished($this->listing, $names));
            }
        }
    }
}
