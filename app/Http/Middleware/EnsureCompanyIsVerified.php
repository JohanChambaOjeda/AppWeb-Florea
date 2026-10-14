<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo empresas verificadas usan la app. Las demás ven "cuenta en revisión".
 * El admin no pasa por esta regla.
 */
class EnsureCompanyIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin() && ! $user->company?->isVerified()) {
            return redirect()->route('account.pending');
        }

        return $next($request);
    }
}
