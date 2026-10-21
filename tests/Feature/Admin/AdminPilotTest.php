<?php

use App\Enums\ListingKind;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use App\Models\Variety;
use App\Support\Metrics;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->variety = Variety::factory()->create(['name' => 'Explorer']);
});

/** Publicación "Busco" de Explorer 60, creada hace $minutesAgo minutos. */
function listingCreatedAgo(int $minutesAgo, ListingKind $kind = ListingKind::Want): Listing
{
    $listing = listingFrom(postharvestUser(), $kind, [
        Variety::firstWhere('name', 'Explorer') ?? Variety::factory()->create(['name' => 'Explorer']),
    ]);

    backdate($listing, $minutesAgo);

    return $listing;
}

/** Oferta sobre esa publicación, creada hace $minutesAgo minutos. */
function offerCreatedAgo(Listing $listing, int $minutesAgo): Offer
{
    $offer = offerOn($listing);

    backdate($offer, $minutesAgo);

    return $offer;
}

test('el tiempo hasta la primera oferta promedia solo las que recibieron oferta', function () {
    // Publicada hace 120 min, primera oferta hace 90 → esperó 30 min.
    $a = listingCreatedAgo(120);
    offerCreatedAgo($a, 90);
    // Y una segunda oferta más tarde: no debe contar, solo la primera.
    offerCreatedAgo($a, 20);

    // Publicada hace 60 min, oferta hace 50 → esperó 10 min.
    $b = listingCreatedAgo(60);
    offerCreatedAgo($b, 50);

    // Sin ofertas: no tiene espera que medir, se queda fuera del promedio.
    listingCreatedAgo(300);

    expect(Metrics::timeToFirstOffer())->toBe(['minutes' => 20, 'listings' => 2]);
});

test('sin ofertas todavía, la métrica es nula y no revienta', function () {
    listingCreatedAgo(100);

    expect(Metrics::timeToFirstOffer())->toBe(['minutes' => null, 'listings' => 0]);
});

test('el panel admin muestra los contadores del piloto', function () {
    $listing = listingCreatedAgo(120);
    offerCreatedAgo($listing, 60);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->where('stats.openListings', 1)
            ->where('stats.listingsToday', 1)
            ->where('stats.offersToday', 1)
            ->where('stats.dealsThisWeek', 0)
            ->where('timeToFirstOffer.minutes', 60)
            ->where('timeToFirstOffer.listings', 1));
});

test('el panel cuenta los tratos de la semana', function () {
    $listing = listingCreatedAgo(120);
    $offer = offerCreatedAgo($listing, 60);

    Deal::create([
        'offer_id' => $offer->id,
        'buyer_company_id' => $listing->company_id,
        'seller_company_id' => $offer->company_id,
        'closed_at' => now(),
    ]);

    // Uno viejo, de hace dos semanas: no cuenta.
    $otro = listingCreatedAgo(200);
    $otraOferta = offerCreatedAgo($otro, 100);
    Deal::create([
        'offer_id' => $otraOferta->id,
        'buyer_company_id' => $otro->company_id,
        'seller_company_id' => $otraOferta->company_id,
        'closed_at' => now()->subWeeks(2),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('stats.dealsThisWeek', 1));
});

test('las publicaciones del día listan tipo, empresa, líneas, ofertas y estado', function () {
    $listing = listingCreatedAgo(30);
    offerCreatedAgo($listing, 10);

    // Una de ayer: no sale en el día de hoy.
    backdate(listingCreatedAgo(60), 60 * 24);

    $this->actingAs($this->admin)
        ->get(route('admin.listings.index'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $rows = $page->toArray()['props']['listings']['data'];

            expect($rows)->toHaveCount(1);
            expect($rows[0]['kindLabel'])->toBe('Busco');
            expect($rows[0]['summary'])->toContain('Explorer 60');
            expect($rows[0]['offersCount'])->toBe(1);
            expect($rows[0]['statusLabel'])->toBe('Abierta');
        });
});

test('se pueden ver las publicaciones de otro día', function () {
    backdate(listingCreatedAgo(30), 60 * 24);

    $this->actingAs($this->admin)
        ->get(route('admin.listings.index', ['date' => today()->subDay()->toDateString()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isToday', false)
            ->count('listings.data', 1));
});

test('una fecha inválida no rompe la pantalla: cae en hoy', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.listings.index', ['date' => 'no-es-una-fecha']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('isToday', true));
});

test('los tratos cerrados muestran comprador, vendedor y lo acordado', function () {
    $listing = listingCreatedAgo(120);
    $offer = offerCreatedAgo($listing, 60);

    Deal::create([
        'offer_id' => $offer->id,
        'buyer_company_id' => $listing->company_id,
        'seller_company_id' => $offer->company_id,
        'closed_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.deals.index'))
        ->assertOk()
        ->assertInertia(function ($page) use ($listing, $offer) {
            $rows = $page->toArray()['props']['deals']['data'];

            expect($rows)->toHaveCount(1);
            expect($rows[0]['buyer'])->toBe($listing->company->name);
            expect($rows[0]['seller'])->toBe($offer->company->name);
            expect($rows[0]['what'])->toBe('2 HB Explorer 60');
            expect($rows[0]['pricePerStem'])->toBe('0.45');
        });
});

test('las pantallas nuevas del admin están cerradas a las empresas', function (string $route) {
    $empresa = postharvestUser();

    $this->actingAs($empresa)->get(route($route))->assertForbidden();
})->with(['admin.listings.index', 'admin.deals.index']);
