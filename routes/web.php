<?php

use App\Http\Controllers\AccountPendingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MyOffersController;
use App\Http\Controllers\MyVarietiesController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OfferResponseController;
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
    Route::get('my-varieties', [MyVarietiesController::class, 'edit'])->name('my-varieties.edit');
    Route::put('my-varieties', [MyVarietiesController::class, 'update'])->name('my-varieties.update');

    // Rutas de la app: un productor sin variedades pasa primero por "Mis variedades".
    Route::middleware('grower.onboarded')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('listings/create', [ListingController::class, 'create'])->name('listings.create');
        Route::post('listings', [ListingController::class, 'store'])->name('listings.store');
        Route::get('listings/{listing}', [ListingController::class, 'show'])->name('listings.show');

        Route::post('listings/{listing}/close', [ListingController::class, 'close'])->name('listings.close');

        // Mi oferta en una publicación: una sola por publicación (regla 3).
        Route::get('listings/{listing}/offer', [OfferController::class, 'edit'])->name('offers.edit');
        Route::post('listings/{listing}/offer', [OfferController::class, 'save'])->name('offers.save');
        Route::delete('listings/{listing}/offer', [OfferController::class, 'withdraw'])->name('offers.withdraw');

        // Quien publicó responde las ofertas que recibió.
        Route::post('offers/{offer}/accept', [OfferResponseController::class, 'accept'])->name('offers.accept');
        Route::post('offers/{offer}/reject', [OfferResponseController::class, 'reject'])->name('offers.reject');

        Route::get('my-offers', [MyOffersController::class, 'index'])->name('my-offers.index');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
