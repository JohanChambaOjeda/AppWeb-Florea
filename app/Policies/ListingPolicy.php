<?php

namespace App\Policies;

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
     */
    public function create(User $user): Response
    {
        if ($user->isAdmin()) {
            return Response::deny('El administrador no publica.');
        }

        return $user->hasVerifiedCompany()
            ? Response::allow()
            : Response::deny('Tu cuenta todavía está en revisión.');
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
        return $this->create($user)->allowed()
            ? $this->manage($user, $listing)
            : $this->create($user);
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
