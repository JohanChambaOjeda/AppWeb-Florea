<?php

namespace App\Policies;

use App\Enums\ListingKind;
use App\Enums\OfferStatus;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Reglas de las publicaciones. Todo pasa por aquí: los controladores solo
 * preguntan, no deciden.
 */
class ListingPolicy
{
    /**
     * Publicar exige empresa verificada (regla 1). El admin no publica.
     *
     * Y regla 8: cada tipo publica lo suyo y solo lo suyo. El productor
     * publica "Disponible" y la poscosecha "Busco". Sin $kind solo se
     * comprueba que la empresa pueda publicar algo, que es lo que necesita
     * la pantalla para decidir si enseña el botón.
     */
    public function create(User $user, ?ListingKind $kind = null): Response
    {
        if ($user->isAdmin()) {
            return Response::deny('El administrador no publica.');
        }

        if (! $user->hasVerifiedCompany()) {
            return Response::deny('Tu cuenta todavía está en revisión.');
        }

        if ($kind !== null && $user->company->type->publishes() !== $kind) {
            return Response::deny($user->company->type->publishHint());
        }

        return Response::allow();
    }

    /**
     * Cualquier empresa verificada ve cualquier publicación; el admin también.
     */
    public function view(User $user, Listing $listing): Response
    {
        return $user->isAdmin() || $user->hasVerifiedCompany()
            ? Response::allow()
            : Response::deny('Tu cuenta todavía está en revisión.');
    }

    /**
     * Solo quien publicó administra su publicación (ver ofertas, cerrar).
     */
    public function manage(User $user, Listing $listing): Response
    {
        return $listing->belongsToCompany($user->company)
            ? Response::allow()
            : Response::deny('Esta publicación no es de tu empresa.');
    }

    /**
     * Duplicar sirve para los pedidos que se repiten: vale sobre una propia
     * en cualquier estado, abierta, cerrada o vencida.
     */
    public function duplicate(User $user, Listing $listing): Response
    {
        // Con el kind de la publicación original: duplicar un "Busco" viejo
        // de cuando la regla no existía tampoco vale.
        $create = $this->create($user, $listing->kind);

        return $create->allowed() ? $this->manage($user, $listing) : $create;
    }

    /**
     * Ofertar (o editar mi oferta) en una publicación ajena. Reglas 1, 2 y 3.
     */
    public function offer(User $user, Listing $listing): Response
    {
        if ($user->isAdmin()) {
            return Response::deny('El administrador no oferta.');
        }

        if (! $user->hasVerifiedCompany()) {
            return Response::deny('Tu cuenta todavía está en revisión.');
        }

        if ($listing->belongsToCompany($user->company)) {
            return Response::deny('No puedes ofertar en tu propia publicación.');
        }

        // Regla 8: el productor oferta en los "Busco" de poscosechas y la
        // poscosecha en los "Disponible" de productores. Se comprueban las
        // dos puntas, porque pueden quedar publicaciones viejas de cuando la
        // regla no existía: un "Busco" de un productor no lo oferta nadie.
        if ($user->company->type->offersOn() !== $listing->kind
            || $listing->company->type !== $listing->kind->publishedBy()) {
            return Response::deny('Esta publicación no es para tu tipo de empresa.');
        }

        if (! $listing->isOpen()) {
            return Response::deny('Esta publicación ya no recibe ofertas.');
        }

        $mine = $listing->offers()->where('company_id', $user->company->id)->first();

        if ($mine !== null && ! $mine->canBeReplaced()) {
            return Response::deny(match ($mine->status) {
                OfferStatus::Accepted => 'Tu oferta en esta publicación ya fue aceptada.',
                default => 'Tu oferta en esta publicación fue rechazada.',
            });
        }

        return Response::allow();
    }

    /**
     * "Ya completé": cerrar una propia que siga abierta.
     */
    public function close(User $user, Listing $listing): Response
    {
        if (! $listing->belongsToCompany($user->company)) {
            return Response::deny('Esta publicación no es de tu empresa.');
        }

        return $listing->isOpen()
            ? Response::allow()
            : Response::deny('Esta publicación ya está cerrada.');
    }
}
