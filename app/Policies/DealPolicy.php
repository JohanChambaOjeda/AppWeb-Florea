<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DealPolicy
{
    /**
     * Calificar: solo las dos empresas del trato, y solo una vez.
     */
    public function rate(User $user, Deal $deal): Response
    {
        $company = $user->company;

        if ($company === null || ! $deal->involves($company)) {
            return Response::deny('Este trato no es de tu empresa.');
        }

        return $deal->ratedBy($company)
            ? Response::deny('Ya calificaste este trato.')
            : Response::allow();
    }
}
