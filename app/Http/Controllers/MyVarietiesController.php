<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Variety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mis variedades": lo que siembra un productor y en qué largos.
 * Con esto se le avisará de los pedidos que le interesan.
 */
class MyVarietiesController extends Controller
{
    public function edit(Request $request): Response
    {
        $company = $this->growerCompany($request);

        $selected = $company->companyVarieties()
            ->get(['variety_id', 'lengths'])
            ->mapWithKeys(fn ($row) => [$row->variety_id => $row->lengths]);

        return Inertia::render('my-varieties/edit', [
            'varieties' => Variety::active()->orderBy('name')->get(['id', 'name', 'color']),
            'selected' => (object) $selected->all(),
            'standardLengths' => config('florea.standard_lengths'),
            'customLength' => config('florea.custom_length'),
            'onboarding' => $selected->isEmpty(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->growerCompany($request);
        $min = config('florea.custom_length.min');
        $max = config('florea.custom_length.max');

        $data = $request->validate([
            'varieties' => ['required', 'array', 'min:1'],
            'varieties.*.variety_id' => ['required', 'integer', 'distinct', 'exists:varieties,id,is_active,1'],
            'varieties.*.lengths' => ['required', 'array', 'min:1'],
            'varieties.*.lengths.*' => ['required', 'integer', "between:{$min},{$max}", 'distinct'],
        ], [
            'varieties.required' => 'Marca al menos una variedad.',
            'varieties.min' => 'Marca al menos una variedad.',
            'varieties.*.lengths.required' => 'Elige al menos un largo.',
            'varieties.*.lengths.min' => 'Elige al menos un largo.',
            'varieties.*.lengths.*.between' => "El largo debe estar entre {$min} y {$max} cm.",
            'varieties.*.variety_id.exists' => 'Esa variedad ya no está disponible.',
        ]);

        $wasOnboarding = ! $company->companyVarieties()->exists();

        DB::transaction(function () use ($company, $data) {
            $chosen = collect($data['varieties'])->mapWithKeys(fn ($item) => [
                $item['variety_id'] => ['lengths' => collect($item['lengths'])->map(fn ($l) => (int) $l)->unique()->sort()->values()->all()],
            ]);

            // Solo se reemplazan las variedades activas: si el admin desactivó una
            // que el productor ya tenía, no se la borramos en silencio.
            $keepInactive = $company->varieties()
                ->where('varieties.is_active', false)
                ->get()
                ->mapWithKeys(fn (Variety $v) => [$v->id => ['lengths' => $v->pivot->lengths]]);

            $company->varieties()->sync($chosen->all() + $keepInactive->all());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tus variedades quedaron guardadas.']);

        return $wasOnboarding ? to_route('dashboard') : to_route('my-varieties.edit');
    }

    private function growerCompany(Request $request): Company
    {
        $company = $request->user()->company;

        abort_unless($company?->isGrower(), 403, 'Esta pantalla es solo para productores.');

        return $company;
    }
}
