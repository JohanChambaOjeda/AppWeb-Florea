<?php

namespace App\Providers;

use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // enforceMorphMap exige que TODO modelo que participe en una relación
        // polimórfica esté aquí. User entra porque la tabla notifications
        // guarda notifiable_type: sin él, cada notificación revienta.
        Relation::enforceMorphMap([
            'listing' => Listing::class,
            'offer' => Offer::class,
            'user' => User::class,
        ]);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Usuarios que se registran desde el celular: mínimo 8 caracteres, sin más reglas.
        Password::defaults(fn (): Password => Password::min(8));
    }

    /**
     * Topes por empresa y por día.
     *
     * No son para castigar a nadie: una poscosecha activa publica cinco o
     * seis veces al día, así que 20 sobra. Están para que un error de la
     * app (o alguien con malas intenciones) no llene el inicio de todos.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('publish', fn (Request $request) => Limit::perDay(20)
            ->by('publish:'.($request->user()->company_id ?? $request->ip()))
            ->response(fn () => back()->withErrors([
                'items' => 'Llegaste al máximo de publicaciones por hoy. Escríbenos si necesitas más.',
            ])));

        RateLimiter::for('offer', fn (Request $request) => Limit::perDay(50)
            ->by('offer:'.($request->user()->company_id ?? $request->ip()))
            ->response(fn () => back()->withErrors([
                'items' => 'Llegaste al máximo de ofertas por hoy. Escríbenos si necesitas más.',
            ])));
    }
}
