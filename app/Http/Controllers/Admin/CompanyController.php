<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyType;
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
}
