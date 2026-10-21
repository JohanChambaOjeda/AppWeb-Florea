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

test('la poscosecha ve todos los "Busco" de otras empresas, coincidan o no', function () {
    [$freedom, $explorer] = [Variety::factory()->create(), Variety::factory()->create()];

    $viewer = postharvestUser();
    $a = listingFrom(postharvestUser(), ListingKind::Want, [$freedom]);
    $b = listingFrom(growerUser($explorer), ListingKind::Want, [$explorer]);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) use ($a, $b) {
            expect(listingIds($page))->toContain($a->id)->toContain($b->id);
        });
});

test('mis propias publicaciones no salen en "Pedidos para mí"', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvestUser();
    $own = listingFrom($viewer, ListingKind::Want, [$variety]);

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

test('"Pedidos para mí" no trae los "Disponible" ni al revés', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvestUser();
    $want = listingFrom(postharvestUser(), ListingKind::Want, [$variety]);
    $have = listingFrom(postharvestUser(), ListingKind::Have, [$variety]);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => expect(listingIds($page))->toEqual([$want->id]));

    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'available']))
        ->assertInertia(fn ($page) => expect(listingIds($page))->toEqual([$have->id]));
});

test('una publicación vencida o cerrada no aparece en los listados', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvestUser();

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
