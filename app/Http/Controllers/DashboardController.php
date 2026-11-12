<?php

namespace App\Http\Controllers;

use App\Enums\HomeTab;
use App\Enums\ListingKind;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Listing;
use App\Models\Variety;
use App\Support\ListingPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inicio de una empresa: los pedidos que le interesan, la flor disponible
 * de otros y sus propias publicaciones.
 */
class DashboardController extends Controller
{
    private const PER_PAGE = 10;

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return to_route('admin.dashboard');
        }

        $company = $user->company;

        // Regla 8: cada tipo tiene dos pestañas, la suya y "Mías". Si pide
        // la del otro tipo por URL, cae en la que le toca en vez de ver una
        // lista vacía sin explicación.
        $others = $this->othersTab($company);
        $asked = HomeTab::tryFrom((string) $request->query('tab'));
        $tab = $asked === HomeTab::Mine ? HomeTab::Mine : $others;

        // Un productor solo ve los pedidos de sus variedades, salvo que
        // encienda "Ver todos". Una poscosecha siempre los ve todos.
        $showAll = $company->isPostharvest() || $request->boolean('all');

        $varietyId = $request->integer('variety_id') ?: null;
        $length = $request->integer('length') ?: null;

        return Inertia::render('dashboard', [
            'tab' => $tab->value,
            'tabs' => $this->tabCounts($company, $showAll),
            // Regla 8: lo único que esta empresa puede publicar. La pantalla
            // pinta un solo botón, no dos.
            'publishes' => $company->type->publishes()->value,
            'listings' => Inertia::scroll(fn () => $this->paginate($tab, $company, $showAll, $varietyId, $length)),
            'filters' => [
                'all' => $showAll,
                'variety_id' => $varietyId,
                'length' => $length,
            ],
            'canFilterAll' => $company->isGrower(),
            // Solo "Disponible hoy" tiene filtros, así que el catálogo se
            // manda únicamente en esa pestaña.
            'varieties' => $tab === HomeTab::Available
                ? Variety::active()->orderBy('name')->get(['id', 'name', 'color'])
                : [],
            'standardLengths' => config('florea.standard_lengths'),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(HomeTab $tab, Company $company, bool $showAll, ?int $varietyId, ?int $length): LengthAwarePaginator
    {
        $query = match ($tab) {
            HomeTab::ForMe => $this->forMe($company, $showAll),
            HomeTab::Available => $this->available($company, $varietyId, $length),
            HomeTab::Mine => $this->mine($company),
        };

        return $query
            ->with(['company.zone', 'items.variety'])
            ->withCount([
                'offers',
                'offers as new_offers_count' => fn (Builder $q) => $q->where('status', OfferStatus::Sent),
            ])
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Listing $listing) => ListingPresenter::card($listing, $company));
    }

    /**
     * "Pedidos para mí": los "Busco" abiertos de otras empresas verificadas.
     * Para un productor, solo los que piden alguna variedad suya. El largo no
     * se filtra: si piden Freedom y él siembra Freedom, le interesa saberlo
     * aunque el largo no coincida exactamente.
     *
     * @return Builder<Listing>
     */
    private function forMe(Company $company, bool $showAll): Builder
    {
        $query = $this->fromOthers($company, ListingKind::Want);

        if (! $showAll) {
            $query->whereHas(
                'items',
                fn (Builder $items) => $items->whereIn('variety_id', $company->companyVarieties()->select('variety_id')),
            );
        }

        return $this->urgentFirst($query);
    }

    /**
     * "Disponible hoy": los "Disponible" abiertos de otras empresas.
     *
     * @return Builder<Listing>
     */
    private function available(Company $company, ?int $varietyId, ?int $length): Builder
    {
        $query = $this->fromOthers($company, ListingKind::Have);

        if ($varietyId !== null) {
            $query->whereHas('items', fn (Builder $items) => $items->where('variety_id', $varietyId));
        }

        if ($length !== null) {
            $query->whereHas('items', fn (Builder $items) => $items->whereJsonContains('lengths', $length));
        }

        return $this->urgentFirst($query);
    }

    /**
     * Las mías, en cualquier estado: las abiertas primero y las más nuevas antes.
     *
     * @return Builder<Listing>
     */
    private function mine(Company $company): Builder
    {
        return Listing::query()
            ->where('company_id', $company->id)
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('created_at');
    }

    /**
     * Publicaciones abiertas de otras empresas verificadas, y del tipo que
     * según la regla 8 publica eso.
     *
     * Lo segundo no sobra: quedan publicaciones de antes de la regla, como el
     * "Busco" de un productor. Sin este filtro se las seguirían viendo otros
     * productores, que es justo lo que no debe pasar.
     *
     * @return Builder<Listing>
     */
    private function fromOthers(Company $company, ListingKind $kind): Builder
    {
        return Listing::query()
            ->open()
            ->where('kind', $kind)
            ->where('company_id', '!=', $company->id)
            ->whereHas('company', fn (Builder $c) => $c->verified()->where('type', $kind->publishedBy()));
    }

    /**
     * Primero lo que se necesita antes, luego lo más reciente.
     *
     * @param  Builder<Listing>  $query
     * @return Builder<Listing>
     */
    private function urgentFirst(Builder $query): Builder
    {
        return $query->orderByRaw('needed_by is null')
            ->orderBy('needed_by')
            ->orderByDesc('created_at');
    }

    /**
     * La pestaña de "lo de los demás" según el tipo (regla 8): el productor
     * ve los pedidos de poscosechas, la poscosecha ve la flor de productores.
     * Cada uno solo tiene esa y "Mías".
     */
    private function othersTab(Company $company): HomeTab
    {
        return $company->isGrower() ? HomeTab::ForMe : HomeTab::Available;
    }

    /**
     * Números de las pestañas. El de "Mis publicaciones" son las ofertas sin
     * responder, que es lo que de verdad pide atención.
     *
     * @return array<string, int>
     */
    private function tabCounts(Company $company, bool $showAll): array
    {
        $others = $this->othersTab($company);

        return [
            $others->value => $others === HomeTab::ForMe
                ? $this->forMe($company, $showAll)->count()
                : $this->available($company, null, null)->count(),
            HomeTab::Mine->value => $company->listings()
                ->whereHas('offers', fn (Builder $o) => $o->where('status', OfferStatus::Sent))
                ->count(),
        ];
    }
}
