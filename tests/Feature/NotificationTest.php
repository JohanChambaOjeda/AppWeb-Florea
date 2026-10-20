<?php

use App\Enums\ListingKind;
use App\Enums\ListingStatus;
use App\Enums\OfferStatus;
use App\Jobs\NotifyMatchingGrowers;
use App\Models\Company;
use App\Models\Listing;
use App\Models\User;
use App\Models\Variety;
use App\Notifications\ListingExpired;
use App\Notifications\MatchingListingPublished;
use App\Notifications\OfferReceived;
use App\Notifications\OfferRejected;
use App\Support\NotificationPresenter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/** Productor verificado que siembra las variedades dadas. */
function growerWith(Variety ...$varieties): User
{
    $user = User::factory()->create(['company_id' => Company::factory()->grower()->verified()]);

    foreach ($varieties as $variety) {
        $user->company->varieties()->attach($variety->id, ['lengths' => [60]]);
    }

    return $user;
}

function publisherUser(): User
{
    return User::factory()->create(['company_id' => Company::factory()->postharvest()->verified()]);
}

/** Publicación con una línea por variedad. */
function listingAsking(User $owner, ListingKind $kind, Variety ...$varieties): Listing
{
    $listing = Listing::factory()->create([
        'company_id' => $owner->company_id,
        'user_id' => $owner->id,
        'kind' => $kind,
        'needed_by' => today(),
        'expires_at' => $kind->expiresAt(),
    ]);

    foreach ($varieties as $variety) {
        $listing->items()->create(['variety_id' => $variety->id, 'lengths' => [60], 'unit' => 'hb']);
    }

    return $listing;
}

test('el comando vence las publicaciones pasadas de plazo y rechaza sus ofertas', function () {
    Notification::fake();

    $variety = Variety::factory()->create();
    $publisher = publisherUser();
    $seller = growerWith($variety);

    $vencida = listingAsking($publisher, ListingKind::Want, $variety);
    $vencida->update(['expires_at' => now()->subMinute()]);

    $oferta = $vencida->offers()->create([
        'company_id' => $seller->company_id,
        'user_id' => $seller->id,
        'price_per_stem' => 0.40,
        'delivery' => 'i_deliver',
        'status' => OfferStatus::Sent,
    ]);

    // Una dentro de plazo, que no se debe tocar.
    $vigente = listingAsking($publisher, ListingKind::Want, $variety);

    $this->artisan('listings:expire')->assertSuccessful();

    expect($vencida->refresh()->status)->toBe(ListingStatus::Expired)
        ->and($oferta->refresh()->status)->toBe(OfferStatus::Rejected)
        ->and($vigente->refresh()->status)->toBe(ListingStatus::Open);

    Notification::assertSentTo($publisher, ListingExpired::class);
    Notification::assertSentTo($seller, OfferRejected::class);
});

test('el comando es idempotente: correrlo dos veces no cambia nada más', function () {
    Notification::fake();

    $variety = Variety::factory()->create();
    $listing = listingAsking(publisherUser(), ListingKind::Want, $variety);
    $listing->update(['expires_at' => now()->subMinute()]);

    $this->artisan('listings:expire');
    Notification::assertSentTimes(ListingExpired::class, 1);

    $this->artisan('listings:expire');
    Notification::assertSentTimes(ListingExpired::class, 1);
});

test('una publicación cerrada a mano no la vuelve a tocar el comando', function () {
    Notification::fake();

    $variety = Variety::factory()->create();
    $listing = listingAsking(publisherUser(), ListingKind::Want, $variety);
    $listing->update(['status' => ListingStatus::Closed, 'expires_at' => now()->subMinute()]);

    $this->artisan('listings:expire');

    expect($listing->refresh()->status)->toBe(ListingStatus::Closed);
    Notification::assertNothingSent();
});

test('publicar un "Busco" encola el aviso a los productores', function () {
    Queue::fake();

    $variety = Variety::factory()->create();
    $publisher = publisherUser();

    $this->actingAs($publisher)->post(route('listings.store'), [
        'kind' => 'want',
        'needed_by' => today()->toDateString(),
        'items' => [['variety_id' => $variety->id, 'lengths' => [60], 'unit' => 'hb']],
    ])->assertRedirect();

    Queue::assertPushed(NotifyMatchingGrowers::class);
});

test('el aviso llega solo a los productores que siembran esas variedades', function () {
    Notification::fake();

    [$freedom, $explorer] = [Variety::factory()->create(['name' => 'Freedom']), Variety::factory()->create(['name' => 'Explorer'])];

    $publisher = publisherUser();
    $coincide = growerWith($freedom);
    $noCoincide = growerWith($explorer);
    $otraPoscosecha = publisherUser();

    $listing = listingAsking($publisher, ListingKind::Want, $freedom);

    (new NotifyMatchingGrowers($listing))->handle();

    Notification::assertSentTo($coincide, MatchingListingPublished::class);
    Notification::assertNotSentTo($noCoincide, MatchingListingPublished::class);
    // A las poscosechas no se les avisa: verían todos los "Busco" y sería spam.
    Notification::assertNotSentTo($otraPoscosecha, MatchingListingPublished::class);
});

test('el aviso nombra solo las variedades que el productor siembra', function () {
    Notification::fake();

    [$freedom, $explorer, $mondial] = [
        Variety::factory()->create(['name' => 'Freedom']),
        Variety::factory()->create(['name' => 'Explorer']),
        Variety::factory()->create(['name' => 'Mondial']),
    ];

    $grower = growerWith($freedom, $explorer);
    $listing = listingAsking(publisherUser(), ListingKind::Want, $freedom, $explorer, $mondial);

    (new NotifyMatchingGrowers($listing))->handle();

    Notification::assertSentTo($grower, MatchingListingPublished::class, function ($notification) use ($grower) {
        $data = $notification->toArray($grower);

        expect($data['varieties'])->toEqual(['Explorer', 'Freedom']);
        expect($data['body'])->toContain('Explorer, Freedom')->not->toContain('Mondial');

        return true;
    });
});

test('un "Disponible" no avisa a nadie', function () {
    Notification::fake();

    $variety = Variety::factory()->create();
    $grower = growerWith($variety);
    $listing = listingAsking(publisherUser(), ListingKind::Have, $variety);

    (new NotifyMatchingGrowers($listing))->handle();

    Notification::assertNothingSent();
});

test('a quien publica no se le avisa de su propia publicación', function () {
    Notification::fake();

    $variety = Variety::factory()->create();
    // Un productor que publica un "Busco" de su propia variedad.
    $grower = growerWith($variety);
    $listing = listingAsking($grower, ListingKind::Want, $variety);

    (new NotifyMatchingGrowers($listing))->handle();

    Notification::assertNotSentTo($grower, MatchingListingPublished::class);
});

test('el aviso a productores va encolado, no en la misma petición', function () {
    expect(new MatchingListingPublished(
        listingAsking(publisherUser(), ListingKind::Want, Variety::factory()->create()),
        ['Freedom'],
    ))->toBeInstanceOf(ShouldQueue::class);
});

test('el contador de no leídas viaja en todas las pantallas', function () {
    $variety = Variety::factory()->create();
    $grower = growerWith($variety);
    $listing = listingAsking(publisherUser(), ListingKind::Want, $variety);

    (new NotifyMatchingGrowers($listing))->handle();

    $this->actingAs($grower)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('unreadNotifications', 1));
});

test('la campana lista las notificaciones y se pueden marcar leídas', function () {
    $variety = Variety::factory()->create();
    $grower = growerWith($variety);
    $listing = listingAsking(publisherUser(), ListingKind::Want, $variety);

    (new NotifyMatchingGrowers($listing))->handle();

    // La lista es una prop opcional (solo se resuelve al abrir la campana),
    // así que se comprueba la forma directamente en el presenter.
    $items = NotificationPresenter::latestFor($grower);

    expect($items)->toHaveCount(1)
        ->and($items[0]['kind'])->toBe('matching_listing')
        ->and($items[0]['read'])->toBeFalse()
        ->and($items[0]['listingId'])->toBe($listing->id)
        ->and($items[0]['url'])->toBe(route('listings.show', $listing))
        ->and($items[0]['title'])->toBe('Están buscando tu flor');

    // Y que la prop opcional NO viaja en una carga normal.
    $this->actingAs($grower)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => expect($page->toArray()['props'])->not->toHaveKey('notifications'));

    $this->actingAs($grower)->post(route('notifications.read-all'))->assertRedirect();

    expect($grower->refresh()->unreadNotifications()->count())->toBe(0);
});

test('solo se pueden marcar leídas las notificaciones propias', function () {
    $variety = Variety::factory()->create();
    $mias = growerWith($variety);
    $ajenas = growerWith($variety);

    $listing = listingAsking(publisherUser(), ListingKind::Want, $variety);
    (new NotifyMatchingGrowers($listing))->handle();

    $ajena = $ajenas->notifications()->sole();

    $this->actingAs($mias)->post(route('notifications.read', $ajena->id))->assertRedirect();

    // Sigue sin leer: no era suya.
    expect($ajenas->refresh()->unreadNotifications()->count())->toBe(1);
});

test('ninguna notificación guarda un celular', function () {
    $variety = Variety::factory()->create();
    $publisher = publisherUser();
    $seller = growerWith($variety);
    $listing = listingAsking($publisher, ListingKind::Want, $variety);

    // Se genera el ciclo completo: aviso, oferta, aceptación.
    (new NotifyMatchingGrowers($listing))->handle();

    $offer = $listing->offers()->create([
        'company_id' => $seller->company_id,
        'user_id' => $seller->id,
        'price_per_stem' => 0.45,
        'delivery' => 'i_deliver',
        'status' => OfferStatus::Sent,
    ]);
    $offer->items()->create([
        'listing_item_id' => $listing->items->first()->id,
        'quantity' => 2, 'unit' => 'hb', 'length' => 60,
    ]);

    $publisher->notify(new OfferReceived($offer));
    $this->actingAs($publisher)->post(route('offers.accept', $offer));

    $phones = [$publisher->phone, $seller->phone];
    $stored = DB::table('notifications')->pluck('data')->implode(' ');

    foreach ($phones as $phone) {
        expect($stored)->not->toContain($phone);
        expect($stored)->not->toContain(substr($phone, -9));
    }
});
