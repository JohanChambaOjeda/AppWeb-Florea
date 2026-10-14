<?php

namespace App\Http\Controllers;

use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountPendingController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->hasVerifiedCompany()) {
            return to_route('dashboard');
        }

        $adminPhone = PhoneNumber::normalize(config('florea.admin_phone'));

        return Inertia::render('account/pending', [
            'companyName' => $user->company?->name,
            'rejected' => (bool) $user->company?->isRejected(),
            'rejectionReason' => $user->company?->rejection_reason,
            'whatsappUrl' => $adminPhone ? 'https://wa.me/'.PhoneNumber::whatsapp($adminPhone) : null,
        ]);
    }
}
