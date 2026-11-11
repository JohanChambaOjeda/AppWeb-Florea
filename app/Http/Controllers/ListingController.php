<?php

namespace App\Http\Controllers;

use App\Enums\CutPoint;
use App\Enums\Destination;
use App\Enums\ListingKind;
use App\Enums\ListingStatus;
use App\Enums\OfferStatus;
use App\Enums\Unit;
use App\Http\Requests\StoreListingRequest;
use App\Jobs\NotifyMatchingGrowers;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use App\Models\Variety;
use App\Notifications\OfferRejected;
use App\Support\ListingPresenter;
use App\Support\Notifier;
use App\Support\OfferPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ListingController extends Controller
{
    /**
     * Formulario de "Nueva publicación". Con ?from=ID se abre con las líneas
     * de una publicación propia, para los pedidos que se repiten cada semana.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        Gate::authorize('create', Listing::class);

        $source = $this->duplicateSource($request);

        // Regla 8: el tipo de publicación ya no lo elige quien publica, lo
        // determina su empresa. El ?kind= de la URL solo sirve para avisar
        // con cariño a quien llegó al sitio equivocado.
        $kind = $request->user()->company->type->publishes();
        $asked = ListingKind::tryFrom((string) $request->query('kind'));

        if ($asked !== null && $asked !== $kind) {
            return to_route('dashboard')
                ->with('info', $request->user()->company->type->publishHint());
        }

        return Inertia::render('listings/create', [
            'kind' => $kind->value,
            'duplicatedFrom' => $source?->id,
            'prefill' => $source ? $this->prefill($source) : null,
            'varieties' => Variety::active()->orderBy('name')->get(['id', 'name', 'color']),
            'standardLengths' => config('florea.standard_lengths'),
            'customLength' => config('florea.custom_length'),
            'units' => Unit::options(),
            'destinations' => Destination::options(),
            'cutPoints' => CutPoint::options(),
            'notesMax' => (int) config('florea.notes_max'),
            'neededByDays' => (int) config('florea.needed_by_days'),
        ]);
    }

    /**
     * Detalle de una publicación. En el paso 4 se agrega la comparación de
     * ofertas para quien publicó.
     */
    public function show(Request $request, Listing $listing): Response
    {
        Gate::authorize('view', $listing);

        $listing->loadMissing(['company.zone', 'items.variety'])
            ->loadCount([
                'offers',
                'offers as new_offers_count' => fn ($q) => $q->where('status', OfferStatus::Sent),
            ]);

        $user = $request->user();
        $company = $user->company;
        $isMine = $listing->belongsToCompany($company);

        $mine = $isMine ? null : $listing->offers()
            ->where('company_id', $company->id)
            ->with(['items.listingItem.variety', 'photos', 'deal.ratings'])
            ->first();

        return Inertia::render('listings/show', [
            'listing' => ListingPresenter::detail($listing, $company),
            'myOffer' => $mine !== null ? OfferPresenter::mine($mine, $user) : null,
            // Las ofertas recibidas solo viajan a quien publicó.
            'offers' => $isMine ? $this->offersFor($listing, $user) : null,
            // La Policy decide; la pantalla solo pinta el botón que toque.
            'canOffer' => Gate::allows('offer', $listing),
            'offerDenial' => Gate::inspect('offer', $listing)->message(),
            'canClose' => Gate::allows('close', $listing),
        ]);
    }

    /**
     * "Ya completé": cierra la publicación y rechaza las ofertas que
     * quedaban enviadas (regla 7).
     */
    public function close(Listing $listing): RedirectResponse
    {
        Gate::authorize('close', $listing);

        $rejected = DB::transaction(function () use ($listing) {
            $listing->update(['status' => ListingStatus::Closed]);

            $pending = $listing->offers()->where('status', OfferStatus::Sent)->with('user')->get();

            $listing->offers()
                ->where('status', OfferStatus::Sent)
                ->update(['status' => OfferStatus::Rejected]);

            return $pending;
        });

        foreach ($rejected as $offer) {
            Notifier::send($offer->user, new OfferRejected($offer, becauseListingClosed: true));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $rejected->isEmpty()
                ? 'Publicación cerrada.'
                : 'Publicación cerrada. Avisamos a quienes seguían esperando respuesta.',
        ]);

        return back();
    }

    /**
     * Las ofertas recibidas, para compararlas lado a lado.
     *
     * @return list<array<string, mixed>>
     */
    private function offersFor(Listing $listing, User $user): array
    {
        return array_values(
            $listing->offers()
                ->with(['company.zone', 'items.listingItem.variety', 'photos', 'user', 'deal.ratings'])
                ->orderByRaw("case status when 'accepted' then 0 when 'sent' then 1 else 2 end")
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Offer $offer) => OfferPresenter::forPublisher($offer, $user))
                ->all()
        );
    }

    public function store(StoreListingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $kind = ListingKind::from($data['kind']);

        Gate::authorize('create', [Listing::class, $kind]);
        $user = $request->user();

        $listing = DB::transaction(function () use ($data, $kind, $user) {
            $listing = Listing::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'kind' => $kind,
                'needed_by' => $data['needed_by'],
                'destination' => $data['destination'] ?? null,
                'cut_point' => $data['cut_point'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => ListingStatus::Open,
                'expires_at' => $kind->expiresAt(),
            ]);

            foreach ($data['items'] as $item) {
                $listing->items()->create([
                    'variety_id' => $item['variety_id'],
                    'lengths' => $this->cleanLengths($item['lengths']),
                    'quantity' => $item['quantity'] ?? null,
                    'unit' => $item['unit'],
                ]);
            }

            return $listing;
        });

        // Encolado: un "Busco" de ocho variedades puede tocar a decenas de
        // productores, y quien publica no debe esperar por eso.
        NotifyMatchingGrowers::dispatch($listing);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $kind === ListingKind::Want
                ? 'Tu pedido está publicado. Te avisamos cuando lleguen ofertas.'
                : 'Tu flor disponible ya está publicada.',
        ]);

        return to_route('listings.show', $listing);
    }

    /**
     * @param  array<int, int|string>  $lengths
     * @return list<int>
     */
    private function cleanLengths(array $lengths): array
    {
        $clean = array_values(array_unique(array_map(intval(...), $lengths)));
        sort($clean);

        return $clean;
    }

    /**
     * Publicación propia que se está duplicando, si la hay.
     */
    private function duplicateSource(Request $request): ?Listing
    {
        $id = $request->integer('from');

        if ($id === 0) {
            return null;
        }

        $listing = Listing::with('items')->findOrFail($id);

        Gate::authorize('duplicate', $listing);

        return $listing;
    }

    /**
     * Se copian las líneas y las preferencias; la fecha se vuelve a elegir,
     * porque un pedido repetido siempre es para otro día.
     *
     * @return array<string, mixed>
     */
    private function prefill(Listing $listing): array
    {
        $items = [];

        foreach ($listing->items as $item) {
            $items[] = [
                'variety_id' => $item->variety_id,
                'lengths' => $item->lengths,
                'quantity' => $item->quantity !== null ? (float) $item->quantity : null,
                'unit' => $item->unit?->value,
            ];
        }

        return [
            'items' => $items,
            'destination' => $listing->destination?->value,
            'cut_point' => $listing->cut_point?->value,
            'notes' => $listing->notes,
        ];
    }
}
