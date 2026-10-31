<?php

namespace App\Http\Middleware;

use App\Support\NotificationPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'company' => $user?->company ? [
                    'id' => $user->company->id,
                    'name' => $user->company->name,
                    'type' => $user->company->type->value,
                    'verified' => $user->hasVerifiedCompany(),
                ] : null,
                'isAdmin' => (bool) $user?->isAdmin(),
            ],
            // La campana: el contador viaja en cada respuesta porque es una
            // cuenta indexada y barata. La lista solo se carga al abrir la
            // campana, con un reload parcial de esta prop.
            'unreadNotifications' => $user !== null ? $user->unreadNotifications()->count() : 0,
            'notifications' => $user !== null
                ? Inertia::optional(fn () => NotificationPresenter::latestFor($user))
                : null,
            // Avisos push: la llave pública VAPID la necesita el navegador
            // para suscribirse. Es pública por diseño; la privada se queda
            // en el servidor.
            'push' => [
                'vapidPublicKey' => config('webpush.vapid.public_key'),
                'subscribed' => $user !== null && $user->pushSubscriptions()->exists(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
