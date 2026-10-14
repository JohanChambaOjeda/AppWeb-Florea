<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un productor sin variedades registradas ve primero "Mis variedades",
 * para que le lleguen los pedidos que le interesan.
 */
class EnsureGrowerHasVarieties
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->company;

        if ($company?->isGrower() && ! $company->companyVarieties()->exists()) {
            return redirect()->route('my-varieties.edit');
        }

        return $next($request);
    }
}
