<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VarietyController;
use App\Http\Controllers\Admin\ZoneController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('companies/{company}/verify', [CompanyController::class, 'verify'])->name('companies.verify');
    Route::post('companies/{company}/reject', [CompanyController::class, 'reject'])->name('companies.reject');

    Route::get('varieties', [VarietyController::class, 'index'])->name('varieties.index');
    Route::post('varieties', [VarietyController::class, 'store'])->name('varieties.store');
    Route::put('varieties/{variety}', [VarietyController::class, 'update'])->name('varieties.update');
    Route::post('varieties/{variety}/toggle', [VarietyController::class, 'toggle'])->name('varieties.toggle');
    Route::delete('varieties/{variety}', [VarietyController::class, 'destroy'])->name('varieties.destroy');

    Route::get('zones', [ZoneController::class, 'index'])->name('zones.index');
    Route::post('zones', [ZoneController::class, 'store'])->name('zones.store');
    Route::put('zones/{zone}', [ZoneController::class, 'update'])->name('zones.update');
    Route::post('zones/{zone}/toggle', [ZoneController::class, 'toggle'])->name('zones.toggle');
});
