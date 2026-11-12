<?php

namespace App\Http\Controllers;

use App\Enums\OfferStatus;
use App\Enums\PaymentTerm;
use App\Models\Deal;
use App\Models\Offer;
use App\Notifications\OfferAccepted;
use App\Notifications\OfferRejected;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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
    public function accept(Request $request, Offer $offer): RedirectResponse
    {
        Gate::authorize('respond', $offer);

        $listing = $offer->listing;

        // Quién compra y quién vende sale del tipo de publicación: en un
        // "Busco" el que oferta vende; en un "Disponible", compra.
        $offererSells = $listing->kind->offererSells();

        // El plazo de pago lo pone quien paga, o sea la poscosecha. Si quien
        // ofertó vende, entonces quien acepta es la poscosecha y lo elige
        // ahora. Si quien ofertó compra, ya lo dejó dicho en su oferta y el
        // productor lo vio antes de aceptar.
        $term = $offererSells
            ? $request->validate(
                ['payment_term' => ['required', Rule::enum(PaymentTerm::class)]],
                ['payment_term.required' => 'Di en cuánto tiempo vas a pagar.'],
            )['payment_term']
            : $offer->payment_term;

        DB::transaction(function () use ($offer, $listing, $offererSells, $term) {
            $offer->update(['status' => OfferStatus::Accepted]);

            Deal::create([
                'offer_id' => $offer->id,
                'buyer_company_id' => $offererSells ? $listing->company_id : $offer->company_id,
                'seller_company_id' => $offererSells ? $offer->company_id : $listing->company_id,
                'closed_at' => now(),
                'payment_term' => $term,
            ]);
        });

        // El contador de tratos cambia para las dos: sale en sus tarjetas.
        $offer->listing->company->recomputeReputation();
        $offer->company->recomputeReputation();

        Notifier::send($offer->user, new OfferAccepted($offer));

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

        Notifier::send($offer->user, new OfferRejected($offer));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Oferta rechazada.']);

        return back();
    }
}
