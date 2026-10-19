<?php

use App\Enums\ListingKind;
use App\Enums\ListingStatus;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use App\Models\Variety;
use App\Notifications\OfferAccepted;
use App\Notifications\OfferRejected;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->publisher = User::factory()->create([
        'company_id' => Company::factory()->postharvest()->verified(),
    ]);
});

/** Publicación con dos líneas, de la poscosecha que publica. */
function listingWithItems(User $publisher, ListingKind $kind = ListingKind::Want): Listing
{
    $listing = Listing::factory()->create([
        'company_id' => $publisher->company_id,
        'user_id' => $publisher->id,
        'kind' => $kind,
        'needed_by' => today(),
        'expires_at' => $kind->expiresAt(),
    ]);

    foreach (['Explorer', 'Freedom'] as $name) {
        $listing->items()->create([
            'variety_id' => Variety::factory()->create(['name' => $name])->id,
            'lengths' => [70, 60],
            'quantity' => 3,
            'unit' => 'hb',
        ]);
    }

    return $listing;
}

/**
 * Oferta enviada por un productor sobre la primera línea.
 *
 * El productor lleva una variedad registrada a propósito: sin ella el
 * middleware grower.onboarded lo manda a "Mis variedades" y nunca llega a
 * la Policy.
 */
function offerFrom(Listing $listing, ?User $seller = null, float $price = 0.45): Offer
{
    if ($seller === null) {
        $seller = User::factory()->create([
            'company_id' => Company::factory()->grower()->verified(),
        ]);

        $seller->company->varieties()->attach(
            Variety::factory()->create()->id,
            ['lengths' => [60]],
        );
    }

    $offer = $listing->offers()->create([
        'company_id' => $seller->company_id,
        'user_id' => $seller->id,
        'price_per_stem' => $price,
        'delivery' => 'i_deliver',
        'status' => OfferStatus::Sent,
    ]);

    $offer->items()->create([
        'listing_item_id' => $listing->items->first()->id,
        'quantity' => 3,
        'unit' => 'hb',
        'length' => 70,
    ]);

    return $offer;
}

/** Las props de Inertia como texto, sin escapar las barras de las URLs. */
function propsJson($page): string
{
    return json_encode($page->toArray()['props'], JSON_UNESCAPED_SLASHES) ?: '';
}

test('aceptar crea el trato con comprador y vendedor según el tipo', function () {
    $listing = listingWithItems($this->publisher, ListingKind::Want);
    $offer = offerFrom($listing);

    $this->actingAs($this->publisher)
        ->post(route('offers.accept', $offer))
        ->assertRedirect();

    $deal = Deal::sole();

    expect($offer->refresh()->status)->toBe(OfferStatus::Accepted)
        ->and($deal->offer_id)->toBe($offer->id)
        // En un "Busco" quien oferta vende: el que publicó es el comprador.
        ->and($deal->buyer_company_id)->toBe($listing->company_id)
        ->and($deal->seller_company_id)->toBe($offer->company_id);

    Notification::assertSentTo($offer->user, OfferAccepted::class);
});

test('en un "Disponible" los papeles se invierten', function () {
    $listing = listingWithItems($this->publisher, ListingKind::Have);
    $offer = offerFrom($listing);

    $this->actingAs($this->publisher)->post(route('offers.accept', $offer));

    $deal = Deal::sole();

    // En un "Disponible" quien oferta compra.
    expect($deal->buyer_company_id)->toBe($offer->company_id)
        ->and($deal->seller_company_id)->toBe($listing->company_id);
});

test('se pueden aceptar dos ofertas distintas en la misma publicación', function () {
    $listing = listingWithItems($this->publisher);
    $a = offerFrom($listing);
    $b = offerFrom($listing);

    $this->actingAs($this->publisher)->post(route('offers.accept', $a))->assertRedirect();
    $this->actingAs($this->publisher)->post(route('offers.accept', $b))->assertRedirect();

    expect($a->refresh()->status)->toBe(OfferStatus::Accepted)
        ->and($b->refresh()->status)->toBe(OfferStatus::Accepted)
        ->and(Deal::count())->toBe(2)
        // La publicación sigue abierta: puede faltarle completar más.
        ->and($listing->refresh()->status)->toBe(ListingStatus::Open);
});

test('rechazar deja la oferta en "rejected" y avisa', function () {
    $listing = listingWithItems($this->publisher);
    $offer = offerFrom($listing);

    $this->actingAs($this->publisher)->post(route('offers.reject', $offer))->assertRedirect();

    expect($offer->refresh()->status)->toBe(OfferStatus::Rejected)
        ->and(Deal::count())->toBe(0);

    Notification::assertSentTo($offer->user, OfferRejected::class);
});

test('solo quien publicó puede aceptar o rechazar', function () {
    $listing = listingWithItems($this->publisher);
    $offer = offerFrom($listing);
    $intruso = User::factory()->create(['company_id' => Company::factory()->postharvest()->verified()]);

    $this->actingAs($intruso)->post(route('offers.accept', $offer))->assertForbidden();
    $this->actingAs($offer->user)->post(route('offers.accept', $offer))->assertForbidden();

    expect($offer->refresh()->status)->toBe(OfferStatus::Sent);
});

test('una oferta ya respondida no se vuelve a responder', function () {
    $listing = listingWithItems($this->publisher);
    $offer = offerFrom($listing);

    $this->actingAs($this->publisher)->post(route('offers.accept', $offer));
    $this->actingAs($this->publisher)->post(route('offers.accept', $offer))->assertForbidden();

    expect(Deal::count())->toBe(1);
});

test('"Ya completé" cierra la publicación y rechaza las ofertas pendientes', function () {
    $listing = listingWithItems($this->publisher);
    $aceptada = offerFrom($listing);
    $pendiente = offerFrom($listing);

    $this->actingAs($this->publisher)->post(route('offers.accept', $aceptada));
    $this->actingAs($this->publisher)->post(route('listings.close', $listing))->assertRedirect();

    expect($listing->refresh()->status)->toBe(ListingStatus::Closed)
        ->and($pendiente->refresh()->status)->toBe(OfferStatus::Rejected)
        // La aceptada no se toca.
        ->and($aceptada->refresh()->status)->toBe(OfferStatus::Accepted);

    Notification::assertSentTo($pendiente->user, OfferRejected::class);
});

test('no se puede cerrar la publicación de otra empresa', function () {
    $listing = listingWithItems($this->publisher);
    $otro = User::factory()->create(['company_id' => Company::factory()->postharvest()->verified()]);

    $this->actingAs($otro)->post(route('listings.close', $listing))->assertForbidden();

    expect($listing->refresh()->status)->toBe(ListingStatus::Open);
});

test('el celular aparece solo después del trato, a las dos partes', function () {
    $listing = listingWithItems($this->publisher);
    $offer = offerFrom($listing);
    $seller = $offer->user;

    $sellerPhone = $seller->phone;
    $publisherPhone = $this->publisher->phone;

    // ANTES del trato: ninguno de los dos ve el celular del otro.
    $this->actingAs($this->publisher)
        ->get(route('listings.show', $listing))
        ->assertInertia(fn ($page) => expect(propsJson($page))
            ->not->toContain($sellerPhone)
            ->not->toContain(substr($sellerPhone, -9)));

    $this->actingAs($seller)
        ->get(route('my-offers.index'))
        ->assertInertia(fn ($page) => expect(propsJson($page))
            ->not->toContain($publisherPhone)
            ->not->toContain(substr($publisherPhone, -9)));

    // Se acepta: nace el trato.
    $this->actingAs($this->publisher)->post(route('offers.accept', $offer));

    // DESPUÉS: quien publicó ve el celular de quien ofertó.
    $this->actingAs($this->publisher)
        ->get(route('listings.show', $listing))
        ->assertInertia(function ($page) use ($sellerPhone) {
            $json = propsJson($page);

            expect($json)->toContain(substr($sellerPhone, -9));
            expect($json)->toContain('wa.me/'.ltrim($sellerPhone, '+'));
        });

    // Y quien ofertó ve el de quien publicó, en "Mis ofertas".
    $this->actingAs($seller)
        ->get(route('my-offers.index'))
        ->assertInertia(function ($page) use ($publisherPhone) {
            $json = propsJson($page);

            expect($json)->toContain(substr($publisherPhone, -9));
            expect($json)->toContain('wa.me/'.ltrim($publisherPhone, '+'));
        });
});

test('un tercero no ve el celular aunque el trato exista', function () {
    $listing = listingWithItems($this->publisher);
    $offer = offerFrom($listing);
    $sellerPhone = $offer->user->phone;

    $this->actingAs($this->publisher)->post(route('offers.accept', $offer));

    // Otro productor mira la publicación: no es parte del trato.
    $tercero = User::factory()->create(['company_id' => Company::factory()->grower()->verified()]);
    $tercero->company->varieties()->attach(Variety::factory()->create()->id, ['lengths' => [60]]);

    $this->actingAs($tercero)
        ->get(route('listings.show', $listing))
        ->assertOk()
        ->assertInertia(fn ($page) => expect(propsJson($page))
            ->not->toContain($sellerPhone)
            ->not->toContain(substr($sellerPhone, -9)));
});

test('el mensaje de WhatsApp lleva nombre, empresa y lo acordado', function () {
    $listing = listingWithItems($this->publisher);
    $offer = offerFrom($listing, price: 0.45);

    $this->actingAs($this->publisher)->post(route('offers.accept', $offer));

    $this->actingAs($this->publisher)
        ->get(route('listings.show', $listing))
        ->assertInertia(function ($page) {
            $url = $page->toArray()['props']['offers'][0]['contact']['whatsappUrl'];
            $text = urldecode(parse_url($url, PHP_URL_QUERY) ?? '');

            expect($text)
                ->toContain($this->publisher->name)
                ->toContain($this->publisher->company->name)
                ->toContain('Acepté tu oferta')
                ->toContain('3 HB Explorer 70')
                ->toContain('$0,45 el tallo')
                ->toContain('¿Coordinamos la entrega?');
        });
});

test('quien ofertó recibe el mensaje en la otra dirección', function () {
    $listing = listingWithItems($this->publisher);
    $offer = offerFrom($listing);

    $this->actingAs($this->publisher)->post(route('offers.accept', $offer));

    $this->actingAs($offer->user)
        ->get(route('my-offers.index'))
        ->assertInertia(function ($page) {
            $url = $page->toArray()['props']['offers']['data'][0]['contact']['whatsappUrl'];
            $text = urldecode(parse_url($url, PHP_URL_QUERY) ?? '');

            expect($text)->toContain('Aceptaste mi oferta')->toContain('3 HB Explorer 70');
        });
});
