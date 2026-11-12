<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryResult;
use App\Enums\PaidAsAgreed;
use App\Enums\RejectedRange;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Deal;
use App\Support\OfferPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Calificar un trato, en una sola pantalla.
 */
class RatingController extends Controller
{
    public function create(Request $request, Deal $deal): Response
    {
        Gate::authorize('rate', $deal);

        $company = $request->user()->company;
        $deal->loadMissing(['offer.items.listingItem.variety', 'buyerCompany', 'sellerCompany']);

        $other = $deal->otherCompany($company);
        $isBuyer = $deal->isBuyer($company);

        return Inertia::render('ratings/create', [
            'deal' => [
                'id' => $deal->id,
                'closedAt' => $deal->closed_at->toIso8601String(),
                'what' => OfferPresenter::summary($deal->offer),
                'pricePerStem' => $deal->offer->price_per_stem,
                // El plazo acordado: el productor lo necesita delante para
                // responder si le pagaron como quedaron.
                'paymentTerm' => $deal->payment_term?->label(),
                'paymentDue' => $deal->payment_term
                    ?->dueAfter($deal->closed_at)->toIso8601String(),
            ],
            'other' => [
                'name' => $other->name,
                'typeLabel' => $other->type->label(),
            ],
            // Decide qué preguntas se ven: quien compra reporta la entrega,
            // quien vende reporta el pago.
            'isBuyer' => $isBuyer,
            'deliveryResults' => DeliveryResult::options(),
            'rejectedRanges' => RejectedRange::options(),
            'paidOptions' => PaidAsAgreed::options(),
            'commentMax' => (int) config('florea.notes_max'),
        ]);
    }

    public function store(StoreRatingRequest $request, Deal $deal): RedirectResponse
    {
        Gate::authorize('rate', $deal);

        $data = $request->validated();
        $company = $request->user()->company;
        $other = $deal->otherCompany($company);
        $isBuyer = $deal->isBuyer($company);

        DB::transaction(function () use ($data, $deal, $company, $other, $isBuyer) {
            $deal->ratings()->create([
                'from_company_id' => $company->id,
                'to_company_id' => $other->id,
                'score' => $data['score'],
                // Cada dirección contesta lo suyo; lo del otro queda nulo.
                'on_time' => $data['on_time'] ?? null,
                'paid_as_agreed' => $data['paid_as_agreed'] ?? null,
                'honored_agreement' => $data['honored_agreement'] ?? null,
                'comment' => $data['comment'] ?? null,
            ]);

            // El comprador es quien recibió la flor, así que su respuesta es
            // la que vale para el trato. La del vendedor queda en su
            // calificación, pero no toca estas columnas.
            if ($isBuyer) {
                $range = RejectedRange::from($data['rejected_range']);

                $deal->update([
                    'delivered' => $data['delivered'],
                    'rejected_pct' => $range->percent(),
                ]);
            }

            // Ya no hace falta recordarle a esta empresa.
            DB::table('rating_reminders')
                ->where('deal_id', $deal->id)
                ->where('company_id', $company->id)
                ->delete();
        });

        $other->recomputeReputation();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Gracias. Tu calificación ayuda a los demás a decidir.',
        ]);

        return to_route('dashboard');
    }
}
