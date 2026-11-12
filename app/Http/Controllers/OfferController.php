<?php

namespace App\Http\Controllers;

use App\Enums\Delivery;
use App\Enums\OfferStatus;
use App\Enums\Unit;
use App\Http\Requests\SaveOfferRequest;
use App\Models\Listing;
use App\Models\Offer;
use App\Notifications\OfferReceived;
use App\Support\ListingPresenter;
use App\Support\Notifier;
use App\Support\OfferPresenter;
use App\Support\PhotoStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La oferta de mi empresa en una publicación. Es una sola por publicación
 * (regla 3), así que se trata como un recurso único: se abre, se guarda y
 * se retira, siempre la misma fila.
 */
class OfferController extends Controller
{
    public function edit(Request $request, Listing $listing): Response
    {
        Gate::authorize('offer', $listing);

        $listing->loadMissing(['company.zone', 'items.variety']);
        $mine = $this->myOffer($request, $listing);

        return Inertia::render('offers/edit', [
            'listing' => ListingPresenter::detail($listing, $request->user()->company),
            'offer' => $mine !== null && $mine->canBeReplaced()
                ? OfferPresenter::mine($mine->load(['items', 'photos']))
                : null,
            // Regla 4: en un "Busco" quien oferta vende.
            'sells' => $listing->kind->offererSells(),
            'units' => Unit::options(),
            'deliveries' => Delivery::options(),
            'customLength' => config('florea.custom_length'),
            'maxPhotos' => SaveOfferRequest::MAX_PHOTOS,
            'notesMax' => (int) config('florea.notes_max'),
        ]);
    }

    public function save(SaveOfferRequest $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('offer', $listing);

        $data = $request->validated();
        $user = $request->user();
        $sells = $listing->kind->offererSells();

        $existing = $this->myOffer($request, $listing);
        // Una oferta retirada que vuelve a enviarse cuenta como nueva: hay
        // que volver a avisar. Editar una ya enviada, no.
        $isNew = $existing === null || ! $existing->isSent();

        $offer = DB::transaction(function () use ($data, $listing, $user, $sells, $existing, $request) {
            $offer = $existing ?? new Offer(['listing_id' => $listing->id, 'company_id' => $user->company_id]);

            $offer->fill([
                'listing_id' => $listing->id,
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'price_per_stem' => $data['price_per_stem'] ?? null,
                'delivery' => $data['delivery'],
                // Solo viene cuando quien oferta compra. Si oferta para
                // vender, el plazo lo pone la poscosecha al aceptar.
                'payment_term' => $data['payment_term'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => OfferStatus::Sent,
            ])->save();

            // Las líneas se reemplazan enteras: es más simple y más seguro
            // que intentar casarlas una por una.
            $offer->items()->delete();

            foreach ($data['items'] as $item) {
                $offer->items()->create([
                    'listing_item_id' => $item['listing_item_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'length' => $item['length'],
                ]);
            }

            if ($sells) {
                $this->syncPhotos($offer, $request);
            }

            return $offer;
        });

        if ($isNew) {
            Notifier::send($listing->user, new OfferReceived($offer));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isNew ? 'Tu oferta fue enviada.' : 'Tu oferta quedó actualizada.',
        ]);

        return to_route('listings.show', $listing);
    }

    public function withdraw(Request $request, Listing $listing): RedirectResponse
    {
        $offer = $this->myOffer($request, $listing);

        abort_if($offer === null, 404);

        Gate::authorize('withdraw', $offer);

        $offer->update(['status' => OfferStatus::Withdrawn]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Retiraste tu oferta. Puedes volver a ofertar si quieres.',
        ]);

        return to_route('listings.show', $listing);
    }

    /**
     * Quita las fotos que el oferente descartó y guarda las nuevas.
     */
    private function syncPhotos(Offer $offer, SaveOfferRequest $request): void
    {
        $keep = array_map(intval(...), (array) $request->input('keep_photos', []));

        foreach ($offer->photos()->get() as $photo) {
            if (! in_array($photo->id, $keep, true)) {
                PhotoStorage::delete($photo->path);
                $photo->delete();
            }
        }

        foreach ((array) $request->file('photos', []) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $offer->photos()->create([
                'path' => PhotoStorage::store($file),
                'taken_at' => now(),
            ]);
        }
    }

    private function myOffer(Request $request, Listing $listing): ?Offer
    {
        return $listing->offers()
            ->where('company_id', $request->user()->company_id)
            ->first();
    }
}
