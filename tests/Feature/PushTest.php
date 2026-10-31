<?php

use App\Enums\ListingKind;
use App\Jobs\NotifyMatchingGrowers;
use App\Models\Variety;
use App\Notifications\OfferAccepted;
use App\Notifications\WebPushNotification;
use App\Support\GrowerPush;
use App\Support\Notifier;
use App\Support\PushMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

test('un dispositivo se suscribe y se desuscribe', function () {
    $user = growerUser(Variety::factory()->create());

    $this->actingAs($user)->post(route('push.subscribe'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'keys' => ['p256dh' => 'llave-publica', 'auth' => 'token'],
    ])->assertRedirect();

    expect($user->pushSubscriptions()->count())->toBe(1);

    $this->actingAs($user)->delete(route('push.unsubscribe'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
    ])->assertRedirect();

    expect($user->pushSubscriptions()->count())->toBe(0);
});

test('un usuario puede tener varios dispositivos', function () {
    $user = growerUser(Variety::factory()->create());

    foreach (['celular', 'tablet', 'compu'] as $device) {
        $this->actingAs($user)->post(route('push.subscribe'), [
            'endpoint' => "https://fcm.googleapis.com/fcm/send/{$device}",
            'keys' => ['p256dh' => 'llave', 'auth' => 'token'],
        ]);
    }

    expect($user->pushSubscriptions()->count())->toBe(3);

    // Desuscribir uno no toca los otros.
    $this->actingAs($user)->delete(route('push.unsubscribe'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/tablet',
    ]);

    expect($user->pushSubscriptions()->count())->toBe(2);
});

test('suscribirse dos veces con el mismo endpoint no duplica', function () {
    $user = growerUser(Variety::factory()->create());

    foreach ([1, 2] as $_) {
        $this->actingAs($user)->post(route('push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/mismo',
            'keys' => ['p256dh' => 'llave', 'auth' => 'token'],
        ]);
    }

    expect($user->pushSubscriptions()->count())->toBe(1);
});

test('hace falta sesión para suscribirse', function () {
    $this->post(route('push.subscribe'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/x',
        'keys' => ['p256dh' => 'a', 'auth' => 'b'],
    ])->assertRedirect(route('login'));
});

test('la llave pública VAPID viaja a la pantalla, la privada no', function () {
    $user = growerUser(Variety::factory()->create());

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) {
            $json = propsJson($page);

            expect(props($page)['push']['vapidPublicKey'])
                ->toBe(config('webpush.vapid.public_key'));

            // La privada jamás sale del servidor.
            expect($json)->not->toContain((string) config('webpush.vapid.private_key'));
        });
});

test('el primer "Busco" sale al instante y abre la ventana de agrupación', function () {
    Notification::fake();

    $freedom = Variety::factory()->create(['name' => 'Freedom']);
    $grower = growerUser($freedom);
    $listing = listingFrom(postharvestUser(), ListingKind::Want, [$freedom]);

    (new NotifyMatchingGrowers($listing))->handle();

    // Salió el push al instante...
    Notification::assertSentToTimes($grower, WebPushNotification::class, 1);

    // ...y no quedó nada pendiente, pero sí la ventana abierta.
    expect(DB::table('pending_push_listings')->where('user_id', $grower->id)->count())->toBe(0)
        ->and($grower->refresh()->push_window_until)->not->toBeNull()
        ->and($grower->push_window_until->isFuture())->toBeTrue();
});

test('los siguientes se agrupan en un solo aviso', function () {
    Notification::fake();

    $freedom = Variety::factory()->create(['name' => 'Freedom']);
    $grower = growerUser($freedom);
    $publisher = postharvestUser();

    // Cuatro pedidos seguidos.
    foreach (range(1, 4) as $_) {
        $listing = listingFrom($publisher, ListingKind::Want, [$freedom]);
        (new NotifyMatchingGrowers($listing))->handle();
    }

    // Solo el primero salió; los otros tres esperan.
    Notification::assertSentToTimes($grower, WebPushNotification::class, 1);
    expect(DB::table('pending_push_listings')->where('user_id', $grower->id)->count())->toBe(3);

    // Mientras la ventana siga abierta, el resumen no sale.
    expect(GrowerPush::sendDigests())->toBe(0);

    // Al cerrarse, sale UN aviso con los tres.
    $grower->forceFill(['push_window_until' => now()->subMinute()])->save();

    expect(GrowerPush::sendDigests())->toBe(1);

    Notification::assertSentToTimes($grower, WebPushNotification::class, 2);
    expect(DB::table('pending_push_listings')->where('user_id', $grower->id)->count())->toBe(0)
        // Cerrada la tanda, el siguiente vuelve a ser instantáneo.
        ->and($grower->refresh()->push_window_until)->toBeNull();
});

test('una publicación nunca genera dos push al mismo productor', function () {
    Notification::fake();

    $freedom = Variety::factory()->create(['name' => 'Freedom']);
    $grower = growerUser($freedom);
    $listing = listingFrom(postharvestUser(), ListingKind::Want, [$freedom]);

    // El job corre dos veces (un reintento de la cola, por ejemplo).
    (new NotifyMatchingGrowers($listing))->handle();
    (new NotifyMatchingGrowers($listing))->handle();

    Notification::assertSentToTimes($grower, WebPushNotification::class, 1);
});

test('el resumen dice cuántos pedidos hay', function () {
    $freedom = Variety::factory()->create(['name' => 'Freedom']);
    $grower = growerUser($freedom);
    $publisher = postharvestUser();

    foreach (range(1, 3) as $_) {
        DB::table('pending_push_listings')->insert([
            'user_id' => $grower->id,
            'listing_id' => listingFrom($publisher, ListingKind::Want, [$freedom])->id,
            'created_at' => now(),
        ]);
    }

    Notification::fake();

    GrowerPush::sendDigests();

    Notification::assertSentTo($grower, WebPushNotification::class, function ($notification) use ($grower) {
        $push = $notification->toWebPush($grower)->toArray();

        expect($push['body'])->toBe('Hay 3 pedidos nuevos con tus variedades');

        return true;
    });
});

test('los avisos de ofertas llevan texto corto y enlace', function () {
    Notification::fake();

    $freedom = Variety::factory()->create(['name' => 'Explorer']);
    $publisher = postharvestUser();
    $listing = listingFrom($publisher, ListingKind::Want, [$freedom], [70]);
    $offer = offerOn($listing);

    Notifier::send($offer->user, new OfferAccepted($offer));

    Notification::assertSentTo($offer->user, WebPushNotification::class, function ($notification) use ($offer) {
        $push = $notification->toWebPush($offer->user)->toArray();

        expect($push['title'])->toBe('¡Aceptaron tu oferta!')
            ->and($push['data']['url'])->toBe(route('my-offers.index'));

        return true;
    });
});

test('Notifier manda la campana y el push a la vez', function () {
    Notification::fake();

    $variety = Variety::factory()->create();
    $listing = listingFrom(postharvestUser(), ListingKind::Want, [$variety]);
    $offer = offerOn($listing);

    Notifier::send($offer->user, new OfferAccepted($offer));

    // La campana (base de datos) y el push (cola) son dos notificaciones.
    Notification::assertSentTo($offer->user, OfferAccepted::class);
    Notification::assertSentTo($offer->user, WebPushNotification::class);
});

test('el push va por la cola y la campana no', function () {
    expect(new WebPushNotification(new PushMessage('t', 'b')))
        ->toBeInstanceOf(ShouldQueue::class);

    $variety = Variety::factory()->create();
    $listing = listingFrom(postharvestUser(), ListingKind::Want, [$variety]);

    expect(new OfferAccepted(offerOn($listing)))
        ->not->toBeInstanceOf(ShouldQueue::class);
});
