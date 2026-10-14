<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountPendingController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->company?->isVerified()) {
            return to_route('dashboard');
        }

        return Inertia::render('account/pending', [
            'companyName' => $user->company?->name,
        ]);
    }
}
