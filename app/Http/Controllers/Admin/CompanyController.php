<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyType;
use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public const STATUSES = ['pending', 'verified', 'rejected'];

    /**
     * Pestaña "Por verificar" (por defecto) o "Todas" con filtros de tipo y estado.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['pending', 'all'])],
            'type' => ['nullable', Rule::enum(CompanyType::class)],
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ]);

        $tab = $filters['tab'] ?? 'pending';
        $status = $tab === 'pending' ? 'pending' : ($filters['status'] ?? null);

        $companies = Company::query()
            ->with(['zone:id,name', 'owner:id,company_id,name,phone'])
            // Cuántas malas ha recibido: una empresa con tres de una
            // estrella necesita una llamada, no esperar al promedio.
            ->withCount(['ratingsReceived as low_ratings_count' => fn ($q) => $q->where('score', '<=', RatingController::LOW_SCORE)])
            ->when($status === 'pending', fn ($q) => $q->pendingVerification())
            ->when($status === 'verified', fn ($q) => $q->verified())
            ->when($status === 'rejected', fn ($q) => $q->onlyTrashed())
            ->when($status === null, fn ($q) => $q->withTrashed())
            ->when($tab === 'all' && isset($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->orderBy('created_at', $tab === 'pending' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'type' => $company->type->value,
                'typeLabel' => $company->type->label(),
                'taxId' => $company->tax_id,
                'zone' => $company->zone->name,
                'plan' => $company->plan->value,
                'planLabel' => $company->plan->label(),
                'planUntil' => $company->plan_until?->toDateString(),
                'planNote' => $company->plan_note,
                'addressReference' => $company->address_reference,
                'ownerName' => $company->owner?->name,
                'ownerPhone' => $company->owner ? PhoneNumber::display($company->owner->phone) : null,
                'ownerWhatsapp' => $company->owner ? 'https://wa.me/'.PhoneNumber::whatsapp($company->owner->phone) : null,
                'status' => match (true) {
                    $company->isRejected() => 'rejected',
                    $company->isVerified() => 'verified',
                    default => 'pending',
                },
                'rejectionReason' => $company->rejection_reason,
                'registeredAt' => $company->created_at->toIso8601String(),
                // Los números públicos, aquí mismo: así se ve de un vistazo
                // si una empresa está dando problemas sin cambiar de pantalla.
                'ratingsCount' => $company->ratings_count,
                'ratingsAvg' => $company->ratings_avg !== null ? (float) $company->ratings_avg : null,
                'onTimePct' => $company->isGrower() ? $company->on_time_pct : null,
                'paidPct' => $company->isPostharvest() ? $company->paid_pct : null,
                'lowRatings' => (int) $company->low_ratings_count,
            ]);

        return Inertia::render('admin/companies/index', [
            'companies' => $companies,
            'filters' => [
                'tab' => $tab,
                'type' => $filters['type'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
            'pendingCount' => Company::pendingVerification()->count(),
            'companyTypes' => CompanyType::options(),
        ]);
    }

    public function verify(Request $request, Company $company): RedirectResponse
    {
        abort_if($company->isVerified(), 422, 'La empresa ya está verificada.');

        $company->verify($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$company->name} verificada."]);

        return back();
    }

    public function reject(Request $request, Company $company): RedirectResponse
    {
        abort_if($company->isVerified(), 422, 'No se puede rechazar una empresa verificada.');

        $data = $request->validate(
            ['reason' => ['nullable', 'string', 'max:500']],
            attributes: ['reason' => 'motivo'],
        );

        $company->reject($data['reason'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$company->name} rechazada."]);

        return back();
    }

    /**
     * El admin cambia el plan de una empresa. No hay cobros dentro de la
     * app todavía: esto solo deja anotado quién paga, hasta cuándo y por qué.
     */
    public function updatePlan(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::enum(Plan::class)],
            'plan_until' => ['nullable', 'date'],
            'plan_note' => ['nullable', 'string', 'max:255'],
        ], attributes: [
            'plan' => 'plan',
            'plan_until' => 'vigente hasta',
            'plan_note' => 'nota',
        ]);

        $company->update([
            'plan' => $data['plan'],
            'plan_until' => $data['plan_until'] ?? null,
            'plan_note' => $data['plan_note'] ?? null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Plan de {$company->name} actualizado.",
        ]);

        return back();
    }
}
