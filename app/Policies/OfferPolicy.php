<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OfferPolicy
{
    /**
     * Una oferta la ven dos: quien la hizo y quien publicó.
     */
    public function view(User $user, Offer $offer): Response
    {
        if ($offer->belongsToCompany($user->company)) {
            return Response::allow();
        }

        return $offer->listing->belongsToCompany($user->company)
            ? Response::allow()
            : Response::deny('Esta oferta no es tuya.');
    }

    /**
     * Retirar: solo quien la hizo, y solo mientras siga enviada (regla 3).
     */
    public function withdraw(User $user, Offer $offer): Response
    {
        if (! $offer->belongsToCompany($user->company)) {
            return Response::deny('Esta oferta no es de tu empresa.');
        }

        return $offer->isSent()
            ? Response::allow()
            : Response::deny('Esta oferta ya no se puede retirar.');
    }

    /**
     * Aceptar o rechazar: solo quien publicó, y solo sobre una oferta enviada.
     * Se usa en el paso 4.
     */
    public function respond(User $user, Offer $offer): Response
    {
        if (! $offer->listing->belongsToCompany($user->company)) {
            return Response::deny('Esta publicación no es de tu empresa.');
        }

        if (! $offer->isSent()) {
            return Response::deny('Esta oferta ya fue respondida.');
        }

        return $offer->listing->isOpen()
            ? Response::allow()
            : Response::deny('Esta publicación ya está cerrada.');
    }
}
