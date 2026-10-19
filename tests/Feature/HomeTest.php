<?php

use App\Enums\ListingKind;
use App\Models\Company;
use App\Models\Listing;
use App\Models\User;
use App\Models\Variety;

/**
 * Crea un productor verificado que siembra las variedades dadas.
 *
 * @param  list<Variety>  $varieties
 */
function grower(array $varieties = [], array $lengths = [60]): User
{
    $user = User::factory()->create(['company_id' => Company::factory()->grower()->verified()]);

    foreach ($varieties as $variety) {
        $user->company->varieties()->attach($variety->id, ['lengths' => $lengths]);
    }

    return $user;
}

function postharvest(): User
{
    return User::factory()->create(['company_id' => Company::factory()->postharvest()->verified()]);
}

/**
 * Publicación con una línea de la variedad y los largos dados.
 */
function listingFor(User $owner, Variety $variety, ListingKind $kind = ListingKind::Want, array $lengths = [60]): Listing
{
    $listing = Listing::factory()->create([
        'company_id' => $owner->company_id,
        'user_id' => $owner->id,
        'kind' => $kind,
        'needed_by' => today(),
        'expires_at' => $kind->expiresAt(),
    ]);

    $listing->items()->create(['variety_id' => $variety->id, 'lengths' => $lengths, 'unit' => 'hb']);

    return $listing;
}

/** @return list<int> */
function listingIdsFrom($page): array
{
    return collect($page->toArray()['props']['listings']['data'])->pluck('id')->all();
}

test('el productor ve en "Pedidos para mí" solo los que piden sus variedades', function () {
    [$freedom, $explorer] = [Variety::factory()->create(), Variety::factory()->create()];

    $grower = grower([$freedom]);
    $mine = listingFor(postharvest(), $freedom);
    $other = listingFor(postharvest(), $explorer);

    $this->actingAs($grower)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) use ($mine, $other) {
            expect(listingIdsFrom($page))->toContain($mine->id)->not->toContain($other->id);
        });
});

test('con "Ver todos" el productor ve también los que no coinciden', function () {
    [$freedom, $explorer] = [Variety::factory()->create(), Variety::factory()->create()];

    $grower = grower([$freedom]);
    $mine = listingFor(postharvest(), $freedom);
    $other = listingFor(postharvest(), $explorer);

    $this->actingAs($grower)
        ->get(route('dashboard', ['all' => 1]))
        ->assertInertia(function ($page) use ($mine, $other) {
            expect(listingIdsFrom($page))->toContain($mine->id)->toContain($other->id);
        });
});

test('la poscosecha ve todos los "Busco" de otras empresas, coincidan o no', function () {
    [$freedom, $explorer] = [Variety::factory()->create(), Variety::factory()->create()];

    $viewer = postharvest();
    $a = listingFor(postharvest(), $freedom);
    $b = listingFor(grower([$explorer]), $explorer);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) use ($a, $b) {
            expect(listingIdsFrom($page))->toContain($a->id)->toContain($b->id);
        });
});

test('mis propias publicaciones no salen en "Pedidos para mí"', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvest();
    $own = listingFor($viewer, $variety);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => expect(listingIdsFrom($page))->not->toContain($own->id));
});

test('"Disponible hoy" filtra por variedad y por largo', function () {
    [$freedom, $explorer] = [Variety::factory()->create(), Variety::factory()->create()];

    $viewer = postharvest();
    $freedom60 = listingFor(grower(), $freedom, ListingKind::Have, [60]);
    $freedom80 = listingFor(grower(), $freedom, ListingKind::Have, [80]);
    $explorer60 = listingFor(grower(), $explorer, ListingKind::Have, [60]);

    // Sin filtros: los tres.
    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'available']))
        ->assertInertia(fn ($page) => expect(listingIdsFrom($page))->toHaveCount(3));

    // Por variedad.
    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'available', 'variety_id' => $freedom->id]))
        ->assertInertia(function ($page) use ($freedom60, $freedom80, $explorer60) {
            expect(listingIdsFrom($page))
                ->toContain($freedom60->id)
                ->toContain($freedom80->id)
                ->not->toContain($explorer60->id);
        });

    // Por variedad y largo: el largo vive en una columna JSON.
    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'available', 'variety_id' => $freedom->id, 'length' => 60]))
        ->assertInertia(function ($page) use ($freedom60, $freedom80) {
            expect(listingIdsFrom($page))->toEqual([$freedom60->id])->not->toContain($freedom80->id);
        });
});

test('"Pedidos para mí" no trae los "Disponible" ni al revés', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvest();
    $want = listingFor(postharvest(), $variety, ListingKind::Want);
    $have = listingFor(postharvest(), $variety, ListingKind::Have);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => expect(listingIdsFrom($page))->toEqual([$want->id]));

    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'available']))
        ->assertInertia(fn ($page) => expect(listingIdsFrom($page))->toEqual([$have->id]));
});

test('una publicación vencida o cerrada no aparece en los listados', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvest();

    $open = listingFor(postharvest(), $variety);
    $expired = listingFor(postharvest(), $variety);
    $expired->update(['expires_at' => now()->subHour()]);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => expect(listingIdsFrom($page))->toEqual([$open->id]));
});

test('"Mis publicaciones" trae las mías y cuenta las ofertas sin responder', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvest();
    $mine = listingFor($viewer, $variety);
    listingFor(postharvest(), $variety);

    $mine->offers()->create([
        'company_id' => grower()->company_id,
        'user_id' => User::factory()->create()->id,
        'price_per_stem' => 0.45,
        'delivery' => 'i_deliver',
    ]);

    $this->actingAs($viewer)
        ->get(route('dashboard', ['tab' => 'mine']))
        ->assertInertia(function ($page) use ($mine) {
            $data = $page->toArray()['props']['listings']['data'];

            expect($data)->toHaveCount(1);
            expect($data[0]['id'])->toBe($mine->id);
            expect($data[0]['newOffersCount'])->toBe(1);
        });
});

test('ningún celular viaja en las props del inicio ni del detalle', function () {
    $variety = Variety::factory()->create();
    $viewer = postharvest();
    $other = postharvest();
    $listing = listingFor($other, $variety);

    $phone = $other->phone;

    foreach ([route('dashboard'), route('listings.show', $listing)] as $url) {
        $this->actingAs($viewer)
            ->get($url)
            ->assertOk()
            ->assertInertia(function ($page) use ($phone) {
                $json = json_encode($page->toArray()['props']);

                expect($json)->not->toContain($phone);
                expect($json)->not->toContain(ltrim($phone, '+'));
                expect($json)->not->toContain(substr($phone, -9));
            });
    }
});
