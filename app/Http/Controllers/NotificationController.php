<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Marcar una como leída. Se llama al tocarla, antes de ir a la
     * publicación.
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->whereKey($notification)
            ->update(['read_at' => now()]);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
