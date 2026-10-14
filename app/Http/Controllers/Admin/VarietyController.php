<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Variety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VarietyController extends Controller
{
    /** Sugerencias para el campo color; se puede escribir otro. */
    public const COLOR_SUGGESTIONS = [
        'Rojo', 'Blanco', 'Rosado', 'Fucsia', 'Amarillo', 'Naranja', 'Durazno',
        'Crema', 'Lila', 'Morado', 'Verde', 'Bicolor',
    ];

    public function index(): Response
    {
        $varieties = Variety::query()
            ->withExists(['companies as used_by_companies', 'listingItems as used_in_listings'])
            ->orderBy('name')
            ->get()
            ->map(fn (Variety $variety) => [
                'id' => $variety->id,
                'name' => $variety->name,
                'color' => $variety->color,
                'isActive' => $variety->is_active,
                'inUse' => $variety->used_by_companies || $variety->used_in_listings,
            ]);

        return Inertia::render('admin/varieties/index', [
            'varieties' => $varieties,
            'colorSuggestions' => self::COLOR_SUGGESTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $variety = Variety::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Variedad {$variety->name} creada."]);

        return back();
    }

    public function update(Request $request, Variety $variety): RedirectResponse
    {
        $variety->update($this->validated($request, $variety));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Variedad actualizada.']);

        return back();
    }

    public function toggle(Variety $variety): RedirectResponse
    {
        $variety->update(['is_active' => ! $variety->is_active]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $variety->is_active ? "{$variety->name} activada." : "{$variety->name} desactivada.",
        ]);

        return back();
    }

    public function destroy(Variety $variety): RedirectResponse
    {
        if ($variety->isInUse()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Esta variedad ya se usa. Desactívala en vez de borrarla.']);

            return back();
        }

        $variety->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Variedad {$variety->name} borrada."]);

        return back();
    }

    /**
     * @return array{name: string, color: string|null}
     */
    private function validated(Request $request, ?Variety $variety = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('varieties')->ignore($variety)],
            'color' => ['nullable', 'string', 'max:50'],
        ], attributes: ['name' => 'nombre', 'color' => 'color']);
    }
}
