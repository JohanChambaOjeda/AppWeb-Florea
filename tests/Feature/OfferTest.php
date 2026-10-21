<?php

use App\Enums\ListingKind;
use App\Enums\OfferStatus;
use App\Models\Listing;
use App\Models\Variety;
use App\Notifications\OfferReceived;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Notification::fake();

    $this->variety = Variety::factory()->create();
    $this->seller = growerUser($this->variety);
});

/** Publicación de otra empresa, del tipo que se pida. */
function listingOf(ListingKind $kind, array $lengths = [60, 50]): Listing
{
    return listingFrom(postharvestUser(), $kind, [Variety::factory()->create()], $lengths);
}

function photo(): UploadedFile
{
    return UploadedFile::fake()->image('flor.jpg', 2400, 1800);
}

/** Payload válido de una oferta de venta sobre un "Busco". */
function sellPayload(Listing $listing, array $override = []): array
{
    return [
        'items' => [[
            'listing_item_id' => $listing->items->first()->id,
            'quantity' => 2,
            'unit' => 'hb',
            'length' => 60,
        ]],
        'price_per_stem' => '0.45',
        'delivery' => 'i_deliver',
        'photos' => [photo()],
        ...$override,
    ];
}

test('una oferta de venta en un "Busco" se guarda y avisa a quien publicó', function () {
    $listing = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)
        ->post(route('offers.save', $listing), sellPayload($listing))
        ->assertRedirect(route('listings.show', $listing));

    $offer = $listing->offers()->sole();

    expect($offer->status)->toBe(OfferStatus::Sent)
        ->and($offer->company_id)->toBe($this->seller->company_id)
        ->and((float) $offer->price_per_stem)->toBe(0.45)
        ->and($offer->items)->toHaveCount(1)
        ->and($offer->photos)->toHaveCount(1);

    Notification::assertSentTo($listing->user, OfferReceived::class);
});

test('la foto se guarda redimensionada a 1600 px y como JPEG', function () {
    $listing = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing));

    $path = $listing->offers()->sole()->photos()->sole()->path;

    expect($path)->toStartWith('photos/'.now()->format('Y/m').'/')
        ->and($path)->toEndWith('.jpg');

    Storage::disk('public')->assertExists($path);

    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

    expect(max($width, $height))->toBe(1600);
});

test('una oferta de venta sin foto es rechazada', function () {
    $listing = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)
        ->post(route('offers.save', $listing), sellPayload($listing, ['photos' => []]))
        ->assertSessionHasErrors('photos');

    expect($listing->offers()->count())->toBe(0);
});

test('una oferta de venta sin precio es rechazada', function () {
    $listing = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)
        ->post(route('offers.save', $listing), sellPayload($listing, ['price_per_stem' => null]))
        ->assertSessionHasErrors('price_per_stem');

    expect($listing->offers()->count())->toBe(0);
});

test('una oferta de compra en un "Disponible" no exige foto ni precio', function () {
    $listing = listingOf(ListingKind::Have);

    $this->actingAs($this->seller)
        ->post(route('offers.save', $listing), [
            'items' => [[
                'listing_item_id' => $listing->items->first()->id,
                'quantity' => 3,
                'unit' => 'hb',
                'length' => 50,
            ]],
            'delivery' => 'pickup',
        ])
        ->assertRedirect();

    $offer = $listing->offers()->sole();

    expect($offer->status)->toBe(OfferStatus::Sent)
        ->and($offer->price_per_stem)->toBeNull()
        ->and($offer->photos)->toHaveCount(0);
});

test('en un "Disponible" no se admite foto', function () {
    $listing = listingOf(ListingKind::Have);

    $this->actingAs($this->seller)
        ->post(route('offers.save', $listing), [
            'items' => [[
                'listing_item_id' => $listing->items->first()->id,
                'quantity' => 3, 'unit' => 'hb', 'length' => 50,
            ]],
            'delivery' => 'pickup',
            'photos' => [photo()],
        ])
        ->assertSessionHasErrors('photos');
});

test('no se puede ofertar en una publicación propia', function () {
    $listing = listingOf(ListingKind::Want);
    $owner = $listing->user;

    $this->actingAs($owner)->get(route('offers.edit', $listing))->assertForbidden();
    $this->actingAs($owner)->post(route('offers.save', $listing), sellPayload($listing))->assertForbidden();

    expect($listing->offers()->count())->toBe(0);
});

test('no se puede ofertar dos veces: el segundo envío edita la misma oferta', function () {
    $listing = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing));
    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing, ['price_per_stem' => '0.60']));

    expect($listing->offers()->count())->toBe(1)
        ->and((float) $listing->offers()->sole()->price_per_stem)->toBe(0.60);

    // Editar no vuelve a notificar: solo la primera vez.
    Notification::assertSentToTimes($listing->user, OfferReceived::class, 1);
});

test('retirar deja la oferta en "withdrawn" y permite volver a ofertar', function () {
    $listing = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing));
    $this->actingAs($this->seller)->delete(route('offers.withdraw', $listing))->assertRedirect();

    expect($listing->offers()->sole()->status)->toBe(OfferStatus::Withdrawn);

    // Volver a ofertar reutiliza la misma fila y sí vuelve a avisar.
    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing));

    expect($listing->offers()->count())->toBe(1)
        ->and($listing->offers()->sole()->status)->toBe(OfferStatus::Sent);

    Notification::assertSentToTimes($listing->user, OfferReceived::class, 2);
});

test('una oferta rechazada no se puede volver a enviar', function () {
    $listing = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing));
    $listing->offers()->sole()->update(['status' => OfferStatus::Rejected]);

    $this->actingAs($this->seller)->get(route('offers.edit', $listing))->assertForbidden();
    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing))->assertForbidden();

    expect($listing->offers()->sole()->status)->toBe(OfferStatus::Rejected);
});

test('no se puede ofertar en una publicación vencida o cerrada', function () {
    $listing = listingOf(ListingKind::Want);
    $listing->update(['expires_at' => now()->subHour()]);

    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing))->assertForbidden();

    expect($listing->offers()->count())->toBe(0);
});

test('la oferta solo puede cubrir líneas de esa publicación', function () {
    $listing = listingOf(ListingKind::Want);
    $otra = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)
        ->post(route('offers.save', $listing), sellPayload($listing, [
            'items' => [[
                'listing_item_id' => $otra->items->first()->id,
                'quantity' => 2, 'unit' => 'hb', 'length' => 60,
            ]],
        ]))
        ->assertSessionHasErrors('items.0.listing_item_id');
});

test('el precio admite máximo 2 decimales', function () {
    $listing = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)
        ->post(route('offers.save', $listing), sellPayload($listing, ['price_per_stem' => '0.4567']))
        ->assertSessionHasErrors('price_per_stem');
});

test('editar la oferta puede quitar una foto y subir otra', function () {
    $listing = listingOf(ListingKind::Want);

    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing));

    $old = $listing->offers()->sole()->photos()->sole();

    $this->actingAs($this->seller)->post(route('offers.save', $listing), sellPayload($listing, [
        'keep_photos' => [],
        'photos' => [photo()],
    ]));

    $photos = $listing->offers()->sole()->photos()->get();

    expect($photos)->toHaveCount(1)
        ->and($photos->first()->id)->not->toBe($old->id);

    // La foto descartada se borra del disco, no se queda ocupando espacio.
    Storage::disk('public')->assertMissing($old->path);
});

test('el celular de la otra empresa no viaja al ofertar', function () {
    $listing = listingOf(ListingKind::Want);
    $phone = $listing->user->phone;

    $this->actingAs($this->seller)
        ->get(route('offers.edit', $listing))
        ->assertOk()
        ->assertInertia(function ($page) use ($phone) {
            $json = json_encode($page->toArray()['props']);

            expect($json)->not->toContain($phone);
            expect($json)->not->toContain(substr($phone, -9));
        });
});
