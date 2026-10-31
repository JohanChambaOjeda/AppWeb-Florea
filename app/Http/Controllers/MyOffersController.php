<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Support\OfferPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mis ofertas": en qué quedó cada oferta que hizo mi empresa. Si una fue
 * aceptada, aquí aparece el celular de la otra parte y el botón de WhatsApp.
 */
class MyOffersController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $offers = Offer::query()
            ->where('company_id', $user->company_id)
            ->with([
                'listing.company.zone',
                'listing.user',
                'items.listingItem.variety',
                'photos',
                'user',
                // Para el enlace de calificar, sin una consulta por oferta.
                'deal.ratings',
            ])
            // Primero las aceptadas (hay que coordinar), luego las que
            // esperan respuesta, al final las cerradas.
            ->orderByRaw("case status when 'accepted' then 0 when 'sent' then 1 else 2 end")
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->through(fn (Offer $offer) => OfferPresenter::withListing($offer, $user));

        return Inertia::render('my-offers/index', [
            'offers' => Inertia::scroll(fn () => $offers),
        ]);
    }
}
