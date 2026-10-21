<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\Offer;
use App\Support\Metrics;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $verifiedByType = Company::verified()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'pending' => Company::pendingVerification()->count(),
                'verifiedPostharvest' => (int) ($verifiedByType[CompanyType::Postharvest->value] ?? 0),
                'verifiedGrowers' => (int) ($verifiedByType[CompanyType::Grower->value] ?? 0),
                'openListings' => Listing::open()->count(),
                'listingsToday' => Listing::whereDate('created_at', today())->count(),
                'offersToday' => Offer::whereDate('created_at', today())->count(),
                'dealsThisWeek' => Deal::where('closed_at', '>=', now()->startOfWeek())->count(),
            ],
            // La métrica del piloto: cuánto tarda en llegar la primera oferta.
            'timeToFirstOffer' => Metrics::timeToFirstOffer(),
        ]);
    }
}
