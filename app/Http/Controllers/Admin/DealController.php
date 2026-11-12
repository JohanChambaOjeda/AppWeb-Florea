<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RejectedRange;
use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Rating;
use App\Support\OfferPresenter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tratos cerrados: lo que de verdad cuenta como éxito del piloto.
 */
class DealController extends Controller
{
    public function index(): Response
    {
        $deals = Deal::query()
            ->with([
                'buyerCompany:id,name,type',
                'sellerCompany:id,name,type',
                'offer.items.listingItem.variety:id,name',
                'offer.listing:id,kind,needed_by',
                'ratings.fromCompany:id,name',
            ])
            ->orderByDesc('closed_at')
            ->paginate(30)
            ->through(fn (Deal $deal) => [
                'id' => $deal->id,
                'buyer' => $deal->buyerCompany->name,
                'buyerType' => $deal->buyerCompany->type->label(),
                'seller' => $deal->sellerCompany->name,
                'sellerType' => $deal->sellerCompany->type->label(),
                'closedAt' => $deal->closed_at->toIso8601String(),
                // Lo que se acordó pagar, y cuándo vencía.
                'paymentTerm' => $deal->payment_term?->label(),
                'paymentDue' => $deal->payment_term?->dueAfter($deal->closed_at)->toIso8601String(),
                'listingId' => $deal->offer->listing_id,
                'kindLabel' => $deal->offer->listing->kind->label(),
                // Lo acordado: las líneas de la oferta y el precio por tallo.
                'what' => OfferPresenter::summary($deal->offer),
                'pricePerStem' => $deal->offer->price_per_stem,
                'delivery' => $deal->offer->delivery->label(),
                'delivered' => $deal->delivered?->label(),
                'rejectedRange' => RejectedRange::fromPercent(
                    $deal->rejected_pct !== null ? (float) $deal->rejected_pct : null,
                )?->label(),
                // Los comentarios los lee SOLO el admin: en la app las
                // empresas ven únicamente los números (regla del paso 3).
                'ratings' => $deal->ratings->map(fn (Rating $rating) => [
                    'id' => $rating->id,
                    'from' => $rating->fromCompany->name,
                    'score' => $rating->score,
                    // Cada dirección contestó lo suyo; lo del otro va nulo.
                    'onTime' => $rating->on_time,
                    'paidAsAgreed' => $rating->paid_as_agreed?->label(),
                    'honoredAgreement' => $rating->honored_agreement,
                    'comment' => $rating->comment,
                ])->all(),
            ]);

        return Inertia::render('admin/deals/index', [
            'deals' => $deals,
        ]);
    }
}
