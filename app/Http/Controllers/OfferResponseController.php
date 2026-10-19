<?php

namespace App\Http\Controllers;

use App\Enums\OfferStatus;
use App\Models\Deal;
use App\Models\Offer;
use App\Notifications\OfferAccepted;
use App\Notifications\OfferRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Quien publicó acepta o rechaza las ofertas que recibió.
 *
 * Se pueden aceptar varias en la misma publicación (regla 5): una vendedora
 * que busca ocho variedades normalmente las completa con varios productores.
 * La publicación sigue abierta hasta que la cierre o venza.
 */
class OfferResponseController extends Controller
{
    public function accept(Offer $offer): RedirectResponse
    {
        Gate::authorize('respond', $offer);

        DB::transaction(function () use ($offer) {
            $offer->update(['status' => OfferStatus::Accepted]);

            $listing = $offer->listing;

            // Quién compra y quién vende sale del tipo de publicación: en un
            // "Busco" el que oferta vende; en un "Disponible", compra.
            $offererSells = $listing->kind->offererSells();

            Deal::create([
                'offer_id' => $offer->id,
                'buyer_company_id' => $offererSells ? $listing->company_id : $offer->company_id,
                'seller_company_id' => $offererSells ? $offer->company_id : $listing->company_id,
                'closed_at' => now(),
            ]);
        });

        $offer->user->notify(new OfferAccepted($offer));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Oferta aceptada. Ya puedes ver el celular y escribir por WhatsApp.',
        ]);

        return back();
    }

    public function reject(Offer $offer): RedirectResponse
    {
        Gate::authorize('respond', $offer);

        $offer->update(['status' => OfferStatus::Rejected]);

        $offer->user->notify(new OfferRejected($offer));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Oferta rechazada.']);

        return back();
    }
}
