<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ZoneController extends Controller
{
    public function index(): Response
    {
        $zones = Zone::query()
            ->withCount(['companies' => fn ($q) => $q->withTrashed()])
            ->orderBy('canton')
            ->orderBy('name')
            ->get()
            ->map(fn (Zone $zone) => [
                'id' => $zone->id,
                'name' => $zone->name,
                'canton' => $zone->canton,
                'isActive' => $zone->is_active,
                'companiesCount' => $zone->companies_count,
            ]);

        return Inertia::render('admin/zones/index', ['zones' => $zones]);
    }

    public function store(Request $request): RedirectResponse
    {
        $zone = Zone::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Sector {$zone->name} creado."]);

        return back();
    }

    public function update(Request $request, Zone $zone): RedirectResponse
    {
        $zone->update($this->validated($request, $zone));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sector actualizado.']);

        return back();
    }

    public function toggle(Zone $zone): RedirectResponse
    {
        $zone->update(['is_active' => ! $zone->is_active]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $zone->is_active ? "{$zone->name} activado." : "{$zone->name} desactivado.",
        ]);

        return back();
    }

    /**
     * @return array{name: string, canton: string}
     */
    private function validated(Request $request, ?Zone $zone = null): array
    {
        $canton = $request->input('canton') ?: 'Pedro Moncayo';

        return [
            ...$request->validate([
                'name' => [
                    'required', 'string', 'max:100',
                    Rule::unique('zones')->where('canton', $canton)->ignore($zone),
                ],
                'canton' => ['nullable', 'string', 'max:100'],
            ], attributes: ['name' => 'nombre', 'canton' => 'cantón']),
            'canton' => $canton,
        ];
    }
}
