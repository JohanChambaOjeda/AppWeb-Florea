<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
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
                'listingId' => $deal->offer->listing_id,
                'kindLabel' => $deal->offer->listing->kind->label(),
                // Lo acordado: las líneas de la oferta y el precio por tallo.
                'what' => OfferPresenter::summary($deal->offer),
                'pricePerStem' => $deal->offer->price_per_stem,
                'delivery' => $deal->offer->delivery->label(),
            ]);

        return Inertia::render('admin/deals/index', [
            'deals' => $deals,
        ]);
    }
}
