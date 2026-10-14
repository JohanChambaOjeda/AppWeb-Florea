<?php

namespace App\Providers;

use App\Models\Listing;
use App\Models\Offer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Relation::enforceMorphMap([
            'listing' => Listing::class,
            'offer' => Offer::class,
        ]);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Usuarios que se registran desde el celular: mínimo 8 caracteres, sin más reglas.
        Password::defaults(fn (): Password => Password::min(8));
    }
}
