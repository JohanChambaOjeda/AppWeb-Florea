<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * "¿Algo no funcionó?": el camino corto para que alguien nos cuente que se
 * trabó, sin tener que escribir por WhatsApp.
 */
class FeedbackController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'screen' => ['nullable', 'string', 'max:255'],
        ], [
            'message.required' => 'Cuéntanos qué pasó.',
            'message.max' => 'Hazlo más corto, por favor.',
        ]);

        $user = $request->user();

        Feedback::create([
            'user_id' => $user?->id,
            'company_id' => $user?->company_id,
            'message' => $data['message'],
            'screen' => $data['screen'] ?? null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Gracias. Lo revisamos y te escribimos si hace falta.',
        ]);

        return back();
    }
}
