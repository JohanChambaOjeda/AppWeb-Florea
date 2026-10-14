<?php

use App\Http\Controllers\AccountPendingController;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    // Sin recuperación por SMS en el MVP: se pide ayuda por WhatsApp al admin.
    Route::get('forgot-password', function () {
        $phone = PhoneNumber::normalize(config('florea.admin_phone'));
        $text = rawurlencode('Hola, olvidé mi contraseña de Florea. Mi celular es: ');

        return inertia('auth/forgot-password', [
            'whatsappUrl' => $phone ? 'https://wa.me/'.PhoneNumber::whatsapp($phone).'?text='.$text : null,
        ]);
    })->name('password.request');
});

Route::middleware('auth')->group(function () {
    Route::get('account/pending', AccountPendingController::class)->name('account.pending');
});

Route::middleware(['auth', 'company.verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
