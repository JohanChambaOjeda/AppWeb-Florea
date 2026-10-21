<?php

use App\Enums\ListingStatus;
use App\Enums\OfferStatus;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\Variety;
use App\Support\Metrics;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * El recorrido completo de la semana, tal como lo haría una vendedora y dos
 * productores: publicar, que les llegue el aviso, ofertar con foto, comparar,
 * aceptar dos, ver el contacto y cerrar con "Ya completé".
 *
 * Los demás tests miran una pieza cada uno; este comprueba que encajan.
 */
test('recorrido completo: publicar, ofertar, aceptar dos y cerrar', function () {
    Storage::fake('public');

    $freedom = Variety::factory()->create(['name' => 'Freedom']);
    $explorer = Variety::factory()->create(['name' => 'Explorer']);
    $mondial = Variety::factory()->create(['name' => 'Mondial']);

    $vendedora = postharvestUser();
    // Dos productores que coinciden y uno que no.
    $sanIsidro = growerUser($freedom);
    $elRosal = growerUser($explorer);
    $ajeno = growerUser($mondial);

    // ── 1. La poscosecha publica un "Busco" de dos variedades ───────────
    $this->actingAs($vendedora)->post(route('listings.store'), [
        'kind' => 'want',
        'needed_by' => today()->toDateString(),
        'destination' => 'usa',
        'items' => [
            ['variety_id' => $freedom->id, 'lengths' => [60, 50], 'quantity' => 4, 'unit' => 'hb'],
            ['variety_id' => $explorer->id, 'lengths' => [60], 'quantity' => 3, 'unit' => 'hb'],
        ],
    ])->assertSessionHasNoErrors();

    $listing = Listing::sole();

    expect($listing->expires_at->equalTo($listing->created_at->addHours(48)))->toBeTrue();

    // ── 2. El aviso llega a los dos que coinciden, no al tercero ────────
    // No se llama al Job a mano: en tests la cola es "sync", así que el
    // dispatch del controlador ya corrió. Esto comprueba el cableado real.
    expect($sanIsidro->unreadNotifications()->count())->toBe(1)
        ->and($elRosal->unreadNotifications()->count())->toBe(1)
        ->and($ajeno->unreadNotifications()->count())->toBe(0);

    // ── 3. Al productor le aparece el pedido en "Pedidos para mí" ───────
    $this->actingAs($sanIsidro)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => expect(listingIds($page))->toEqual([$listing->id]));

    // Y sin el trato, no ve el celular de la vendedora en ninguna parte.
    $this->actingAs($sanIsidro)
        ->get(route('listings.show', $listing))
        ->assertInertia(fn ($page) => expect(propsJson($page))
            ->not->toContain(substr($vendedora->phone, -9)));

    // ── 4. Los dos ofertan, con foto y precio ───────────────────────────
    [$lineaFreedom, $lineaExplorer] = $listing->items->all();

    $this->actingAs($sanIsidro)->post(route('offers.save', $listing), [
        'items' => [[
            'listing_item_id' => $lineaFreedom->id,
            'quantity' => 4, 'unit' => 'hb', 'length' => 60,
        ]],
        'price_per_stem' => '0.45',
        'delivery' => 'i_deliver',
        'photos' => [UploadedFile::fake()->image('freedom.jpg', 2000, 1500)],
    ])->assertSessionHasNoErrors();

    $this->actingAs($elRosal)->post(route('offers.save', $listing), [
        'items' => [[
            'listing_item_id' => $lineaExplorer->id,
            'quantity' => 3, 'unit' => 'hb', 'length' => 60,
        ]],
        'price_per_stem' => '0.52',
        'delivery' => 'pickup',
        'photos' => [UploadedFile::fake()->image('explorer.jpg', 2000, 1500)],
    ])->assertSessionHasNoErrors();

    expect($listing->offers()->count())->toBe(2);

    // A la vendedora le llegaron los dos avisos.
    expect($vendedora->unreadNotifications()->count())->toBe(2);

    // ── 5. La vendedora compara las dos ofertas ─────────────────────────
    $this->actingAs($vendedora)
        ->get(route('listings.show', $listing))
        ->assertOk()
        ->assertInertia(function ($page) {
            $offers = props($page)['offers'];

            expect($offers)->toHaveCount(2);
            // Antes de aceptar, ninguna trae contacto.
            foreach ($offers as $offer) {
                expect($offer['contact'])->toBeNull();
                expect($offer['canRespond'])->toBeTrue();
                expect($offer['photos'])->toHaveCount(1);
            }
        });

    // ── 6. Acepta las dos: una vendedora completa con varios ────────────
    $ofertaSanIsidro = $listing->offers()->where('company_id', $sanIsidro->company_id)->sole();
    $ofertaElRosal = $listing->offers()->where('company_id', $elRosal->company_id)->sole();

    $this->actingAs($vendedora)->post(route('offers.accept', $ofertaSanIsidro))->assertRedirect();
    $this->actingAs($vendedora)->post(route('offers.accept', $ofertaElRosal))->assertRedirect();

    expect(Deal::count())->toBe(2)
        // La publicación sigue abierta: aceptar no la cierra.
        ->and($listing->refresh()->status)->toBe(ListingStatus::Open);

    // ── 7. Ahora sí: cada parte ve el celular de la otra ────────────────
    $this->actingAs($vendedora)
        ->get(route('listings.show', $listing))
        ->assertInertia(function ($page) use ($sanIsidro, $elRosal) {
            $json = propsJson($page);

            expect($json)->toContain(substr($sanIsidro->phone, -9))
                ->toContain(substr($elRosal->phone, -9))
                ->toContain('wa.me/'.ltrim($sanIsidro->phone, '+'));
        });

    $this->actingAs($sanIsidro)
        ->get(route('my-offers.index'))
        ->assertInertia(function ($page) use ($vendedora, $elRosal) {
            $json = propsJson($page);

            // Ve el de la vendedora, con quien cerró...
            expect($json)->toContain(substr($vendedora->phone, -9));
            // ...pero no el del otro productor, con quien no tiene nada.
            expect($json)->not->toContain(substr($elRosal->phone, -9));
        });

    // El tercero, ajeno al trato, sigue sin ver ningún celular.
    $this->actingAs($ajeno)
        ->get(route('listings.show', $listing))
        ->assertInertia(fn ($page) => expect(propsJson($page))
            ->not->toContain(substr($vendedora->phone, -9))
            ->not->toContain(substr($sanIsidro->phone, -9)));

    // ── 8. "Ya completé": cierra y no quedan ofertas colgando ───────────
    $this->actingAs($vendedora)->post(route('listings.close', $listing))->assertRedirect();

    expect($listing->refresh()->status)->toBe(ListingStatus::Closed)
        ->and($listing->offers()->where('status', OfferStatus::Sent)->count())->toBe(0)
        ->and($listing->offers()->where('status', OfferStatus::Accepted)->count())->toBe(2);

    // ── 9. El piloto ya tiene su métrica ────────────────────────────────
    expect(Metrics::timeToFirstOffer()['listings'])->toBe(1);

    // Y las dos fotos quedaron en disco, redimensionadas.
    foreach ($listing->offers as $offer) {
        $path = $offer->photos()->sole()->path;

        Storage::disk('public')->assertExists($path);

        [$width] = getimagesizefromstring(Storage::disk('public')->get($path));

        expect($width)->toBe(1600);
    }
});
