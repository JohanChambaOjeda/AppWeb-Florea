<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Las suscripciones a avisos push, una por dispositivo.
 *
 * Un usuario puede tener varias: el celular, la tablet, la computadora de la
 * poscosecha. Cada navegador genera su propio endpoint.
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ], [
            'endpoint.required' => 'Falta el endpoint del navegador.',
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
        );

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $endpoint = $request->validate([
            'endpoint' => ['required', 'string'],
        ])['endpoint'];

        $request->user()->deletePushSubscription($endpoint);

        return back();
    }
}
