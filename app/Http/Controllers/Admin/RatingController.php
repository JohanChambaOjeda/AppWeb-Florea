<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Todas las calificaciones, en las dos direcciones y con los comentarios.
 *
 * Esta es la única pantalla donde se leen los comentarios: las empresas ven
 * números, nunca texto de otros. Un comentario crudo entre dos personas que
 * se conocen del cantón se convierte en pelea; el número no.
 *
 * Durante el piloto es además el sitio para detectar a tiempo lo que no se
 * ve en los promedios: una poscosecha que no paga, un productor que entrega
 * flor abierta. De ahí los filtros por estrellas bajas.
 */
class RatingController extends Controller
{
    /** Lo que se considera una calificación que hay que mirar. */
    public const LOW_SCORE = 2;

    public function index(Request $request): Response
    {
        $direction = $request->string('direction')->toString();
        $companyId = $request->integer('company_id') ?: null;
        $onlyLow = $request->boolean('low');

        $ratings = Rating::query()
            ->with([
                'fromCompany:id,name,type',
                'toCompany:id,name,type',
                'deal:id,offer_id,closed_at,payment_term',
                'deal.offer:id,listing_id',
            ])
            // Quién califica a quién, por tipo de empresa.
            ->when($direction !== '', fn (Builder $q) => $q->whereHas(
                'fromCompany',
                fn (Builder $c) => $c->where('type', $direction === 'grower_to_postharvest'
                    ? CompanyType::Grower
                    : CompanyType::Postharvest),
            ))
            // Una empresa concreta, la califique o la hayan calificado.
            ->when($companyId !== null, fn (Builder $q) => $q->where(
                fn (Builder $w) => $w->where('from_company_id', $companyId)
                    ->orWhere('to_company_id', $companyId),
            ))
            ->when($onlyLow, fn (Builder $q) => $q->where('score', '<=', self::LOW_SCORE))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Rating $rating) => [
                'id' => $rating->id,
                'createdAt' => $rating->created_at->toIso8601String(),
                'from' => $rating->fromCompany->name,
                'fromType' => $rating->fromCompany->type->label(),
                'to' => $rating->toCompany->name,
                'toType' => $rating->toCompany->type->label(),
                'score' => $rating->score,
                // Cada dirección respondió lo suyo; lo del otro va nulo y la
                // pantalla simplemente no lo pinta.
                'onTime' => $rating->on_time,
                'paidAsAgreed' => $rating->paid_as_agreed?->label(),
                'honoredAgreement' => $rating->honored_agreement,
                'paymentTerm' => $rating->deal->payment_term?->label(),
                'comment' => $rating->comment,
                'dealId' => $rating->deal_id,
                'listingId' => $rating->deal->offer->listing_id,
                'closedAt' => $rating->deal->closed_at->toIso8601String(),
            ]);

        return Inertia::render('admin/ratings/index', [
            'ratings' => $ratings,
            'filters' => [
                'direction' => $direction !== '' ? $direction : null,
                'company_id' => $companyId,
                'low' => $onlyLow,
            ],
            // Para el selector: solo las que han participado en alguna.
            'companies' => Company::query()
                ->where(fn (Builder $q) => $q->whereHas('ratingsReceived')->orWhereHas('ratingsGiven'))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Company $company) => [
                    'value' => (string) $company->id,
                    'label' => $company->name,
                ]),
            'lowThisWeek' => self::lowThisWeek(),
            'total' => Rating::count(),
        ]);
    }

    /**
     * Las calificaciones de 1 o 2 estrellas de esta semana. El panel de
     * inicio enseña este número para que no haya que entrar a buscarlas.
     */
    public static function lowThisWeek(): int
    {
        return Rating::where('score', '<=', self::LOW_SCORE)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
    }
}
