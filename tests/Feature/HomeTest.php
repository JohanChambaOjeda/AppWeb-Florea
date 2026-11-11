<?php

use App\Enums\ListingKind;
use App\Models\Variety;

test('el productor ve en "Pedidos para mí" solo los que piden sus variedades', function () {
    [$freedom, $explorer] = [Variety::factory()->create(), Variety::factory()->create()];

    $grower = growerUser($freedom);
    $mine = listingFrom(postharvestUser(), ListingKind::Want, [$freedom]);
    $other = listingFrom(postharvestUser(), ListingKind::Want, [$explorer]);

    $this->actingAs($grower)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) use ($mine, $other) {
            expect(listingIds($page))->toContain($mine->id)->not->toContain($other->id);
        });
});

test('con "Ver todos" el productor ve también los que no coinciden', function () {
    [$freedom, $explorer] = [Variety::factory()->create(), Variety::factory()->create()];

    $grower = growerUser($freedom);
    $mine = listingFrom(postharvestUser(), ListingKind::Want, [$freedom]);
    $other = listingFrom(postharvestUser(), ListingKind::Want, [$explorer]);

    $this->actingAs($grower)
        ->get(route('dashboard', ['all' => 1]))
        ->assertInertia(function ($page) use ($mine, $other) {
            expect(listingIds($page))->toContain($mine->id)->toContain($other->id);
        });
});

/**
 * Regla 8: la poscosecha compra, así que su inicio es la flor que ofrecen
 * los productores. Nunca un "Busco", ni el suyo ni el de nadie.
 */
test('la poscosecha ve los "Disponible" de productores y ningún "Busco"', function () {
    [$freedom, $explorer] = [Variety::factory()->create(), Variety::factory()->create()];

    $viewer = postharvestUser();
    $available = listingFrom(growerUser($freedom), ListingKind::Have, [$freedom]);
    $otherWant = listingFrom(postharvestUser(), ListingKind::Want, [$explorer]);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) use ($available, $otherWant) {
            expect(listingIds($page))
                ->toContain($available->id)
                ->not->toContain($otherWant->id);
        });
});

/**
 * Un "Busco" de un productor solo puede ser de antes de la regla 8. El filtro
 * viejo se lo enseñaba a los demás productores; el nuevo lo esconde.
 */
test('un "Busco" de otro productor no le aparece a un productor', function () {
    $freedom = Variety::factory()->create();

    $viewer = growerUser($freedom);
    $legit = listingFrom(postharvestUser(), ListingKind::Want, [$freedom]);
    $stale = listingFrom(growerUser($freedom), ListingKind::Want, [$freedom]);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) use ($legit, $stale) {
            expect(listingIds($page))
                ->toContain($legit->id)
                ->not->toContain($stale->id);
        });
});

test('mis propias publicaciones no salen en la pestaña de los demás', function () {
    $variety = Variety::factory()->create();
    $viewer = growerUser($variety);
    $own = listingFrom($viewer, ListingKind::Have, [$variety]);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => expect(listingIds($page))->not->toContain($own->id));
});

test('"Disponible hoy" filtra por variedad y por largo', function () {
    [$freedom, $explorer] = [Variety::factory()->create(), Variety::factory()->create()];

    $viewer = postharvestUser();
    $freedom60 = listingFrom(growerUser($freedom), ListingKind::Have, [$freedom], [60]);
    $freedom80 = listingFrom(growerUser($freedom), ListingKind::Have, [$freedom], [80]);
    $explorer60 = listingFrom(growerUser($explorer), ListingKind::Have, [$explorer], [60]);

    // Sin filtros: los tres.
    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'available']))
        ->assertInertia(fn ($page) => expect(listingIds($page))->toHaveCount(3));

    // Por variedad.
    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'available', 'variety_id' => $freedom->id]))
        ->assertInertia(function ($page) use ($freedom60, $freedom80, $explorer60) {
            expect(listingIds($page))
                ->toContain($freedom60->id)
                ->toContain($freedom80->id)
                ->not->toContain($explorer60->id);
        });

    // Por variedad y largo: el largo vive en una columna JSON.
    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'available', 'variety_id' => $freedom->id, 'length' => 60]))
        ->assertInertia(function ($page) use ($freedom60, $freedom80) {
            expect(listingIds($page))->toEqual([$freedom60->id])->not->toContain($freedom80->id);
        });
});

/**
 * Cada tipo tiene dos pestañas: la suya y "Mías". Si pide por URL la del otro
 * tipo, cae en la suya en vez de ver una lista vacía sin explicación.
 */
test('cada tipo solo tiene su pestaña, y la del otro redirige a la suya', function () {
    $variety = Variety::factory()->create();
    $want = listingFrom(postharvestUser(), ListingKind::Want, [$variety]);
    $have = listingFrom(growerUser($variety), ListingKind::Have, [$variety]);

    // La poscosecha compra: ve "Disponible", pida la pestaña que pida.
    $buyer = postharvestUser();

    foreach ([null, 'for-me', 'available'] as $tab) {
        $this->actingAs($buyer)
            ->get(route('dashboard', $tab ? ['tab' => $tab] : []))
            ->assertInertia(fn ($page) => expect(listingIds($page))->toEqual([$have->id]));
    }

    // El productor vende: ve los pedidos.
    $seller = growerUser($variety);

    foreach ([null, 'available', 'for-me'] as $tab) {
        $this->actingAs($seller)
            ->get(route('dashboard', $tab ? ['tab' => $tab] : []))
            ->assertInertia(fn ($page) => expect(listingIds($page))->toEqual([$want->id]));
    }
});

test('una publicación vencida o cerrada no aparece en los listados', function () {
    $variety = Variety::factory()->create();
    $viewer = growerUser($variety);

    $open = listingFrom(postharvestUser(), ListingKind::Want, [$variety]);
    $expired = listingFrom(postharvestUser(), ListingKind::Want, [$variety]);
    $expired->update(['expires_at' => now()->subHour()]);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => expect(listingIds($page))->toEqual([$open->id]));
});

test('"Mis publicaciones" trae las mías y cuenta las ofertas sin responder', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvestUser();
    $mine = listingFrom($viewer, ListingKind::Want, [$variety]);
    listingFrom(postharvestUser(), ListingKind::Want, [$variety]);

    offerOn($mine);

    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'mine']))
        ->assertInertia(function ($page) use ($mine) {
            $data = props($page)['listings']['data'];

            expect($data)->toHaveCount(1);
            expect($data[0]['id'])->toBe($mine->id);
            expect($data[0]['newOffersCount'])->toBe(1);
        });
});

test('ningún celular viaja en las props del inicio ni del detalle', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvestUser();
    $other = postharvestUser();
    $listing = listingFrom($other, ListingKind::Want, [$variety]);

    $phone = $other->phone;

    foreach ([route('dashboard'), route('listings.show', $listing)] as $url) {
        $this->actingAs($viewer)
            ->get($url)
            ->assertOk()
            ->assertInertia(function ($page) use ($phone) {
                expect(propsJson($page))
                    ->not->toContain($phone)
                    ->not->toContain(ltrim($phone, '+'))
                    ->not->toContain(substr($phone, -9));
            });
    }
});
