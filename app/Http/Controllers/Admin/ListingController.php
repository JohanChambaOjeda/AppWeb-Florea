<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OfferStatus;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Publicaciones del día, para seguir el pulso del piloto.
 */
class ListingController extends Controller
{
    public function index(Request $request): Response
    {
        $date = $this->date($request);

        $listings = Listing::query()
            ->whereDate('created_at', $date)
            ->with(['company:id,name,type', 'items.variety:id,name'])
            ->withCount([
                'offers',
                'offers as accepted_offers_count' => fn (Builder $q) => $q->where('status', OfferStatus::Accepted),
            ])
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Listing $listing) => [
                'id' => $listing->id,
                'kind' => $listing->kind->value,
                'kindLabel' => $listing->kind->label(),
                'company' => $listing->company->name,
                'companyType' => $listing->company->type->label(),
                'summary' => $listing->items
                    ->map(fn (ListingItem $item) => $item->variety->name.' '.implode('/', $item->lengths))
                    ->implode(' · '),
                'offersCount' => (int) ($listing->offers_count ?? 0),
                'acceptedCount' => (int) ($listing->accepted_offers_count ?? 0),
                'status' => $listing->status->value,
                'statusLabel' => $listing->status->label(),
                'createdAt' => $listing->created_at->toIso8601String(),
            ]);

        return Inertia::render('admin/listings/index', [
            'listings' => $listings,
            'date' => $date->toDateString(),
            'isToday' => $date->isToday(),
        ]);
    }

    /**
     * La app usa CarbonImmutable (ver AppServiceProvider), así que el tipo
     * de retorno es la interfaz, no la clase concreta.
     */
    private function date(Request $request): CarbonInterface
    {
        $raw = $request->string('date')->toString();

        if ($raw === '') {
            return today();
        }

        return rescue(fn () => Carbon::parse($raw)->startOfDay(), today(), report: false);
    }
}
