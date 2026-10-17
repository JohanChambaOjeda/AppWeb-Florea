<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMyVarietiesRequest;
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

    public function update(UpdateMyVarietiesRequest $request): RedirectResponse
    {
        $company = $this->growerCompany($request);
        $chosen = $request->chosenVarieties();

        $wasOnboarding = ! $company->companyVarieties()->exists();

        DB::transaction(function () use ($company, $chosen) {
            // Solo se reemplazan las variedades activas: si el admin desactivó una
            // que el productor ya tenía, no se la borramos en silencio.
            $keepInactive = [];

            foreach ($company->varieties()->where('varieties.is_active', false)->get() as $variety) {
                $keepInactive[$variety->id] = ['lengths' => $variety->pivot->lengths];
            }

            $company->varieties()->sync($chosen + $keepInactive);
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
