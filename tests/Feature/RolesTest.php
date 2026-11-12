<?php

use App\Enums\ListingKind;
use App\Enums\ListingStatus;
use App\Enums\OfferStatus;
use App\Jobs\NotifyMatchingGrowers;
use App\Models\Listing;
use App\Models\Variety;
use App\Notifications\MatchingListingPublished;
use Illuminate\Support\Facades\Notification;

/**
 * REGLA 8: el productor vende y la poscosecha compra. Nunca al revés.
 *
 * Es la regla que más superficie toca —publicar, ofertar, el inicio y los
 * avisos—, así que se prueba entera en un solo sitio.
 */
beforeEach(function () {
    $this->variety = Variety::factory()->create(['name' => 'Freedom']);
    $this->grower = growerUser($this->variety);
    $this->postharvest = postharvestUser();
});

/** Payload mínimo y válido del formulario de publicación. */
function rolePayload(string $kind, Variety $variety): array
{
    return [
        'kind' => $kind,
        'needed_by' => today()->toDateString(),
        'items' => [[
            'variety_id' => $variety->id,
            'lengths' => [60],
            'unit' => 'hb',
        ]],
    ];
}

// ── Publicar ────────────────────────────────────────────────────────────

test('cada tipo solo publica lo suyo', function (string $who, string $kind, bool $allowed) {
    $user = $who === 'grower' ? $this->grower : $this->postharvest;

    $response = $this->actingAs($user)
        ->post(route('listings.store'), rolePayload($kind, $this->variety));

    // 403, no un error de validación: el formulario nunca manda el tipo
    // equivocado, así que si llega es una petición armada a mano.
    $allowed ? $response->assertRedirect() : $response->assertForbidden();

    expect(Listing::where('kind', $kind)->count())->toBe($allowed ? 1 : 0);
})->with([
    'el productor publica "Disponible"' => ['grower', 'have', true],
    'el productor NO publica "Busco"' => ['grower', 'want', false],
    'la poscosecha publica "Busco"' => ['postharvest', 'want', true],
    'la poscosecha NO publica "Disponible"' => ['postharvest', 'have', false],
]);

test('el formulario se abre siempre en el tipo que le toca', function () {
    // Aunque pida el otro por URL, lo devuelve al inicio con una explicación.
    $this->actingAs($this->grower)
        ->get(route('listings.create', ['kind' => 'want']))
        ->assertRedirect(route('dashboard'));

    expect(session('inertia.flash_data.toast.message'))
        ->toBe('Como productor, aquí publicas la flor que tienes disponible.');

    $this->actingAs($this->postharvest)
        ->get(route('listings.create', ['kind' => 'have']))
        ->assertRedirect(route('dashboard'));

    expect(session('inertia.flash_data.toast.message'))
        ->toBe('Como poscosecha, aquí publicas la flor que te falta.');

    // Y sin pedir nada, entra sin problema.
    $this->actingAs($this->grower)
        ->get(route('listings.create'))
        ->assertOk();
});

test('no se puede duplicar lo que ya no le corresponde', function (string $who, string $kind) {
    $user = $who === 'grower' ? $this->grower : $this->postharvest;

    // Una publicación suya de antes de la regla 8.
    $stale = listingFrom($user, ListingKind::from($kind), [$this->variety]);

    $this->actingAs($user)
        ->get(route('listings.create', ['from' => $stale->id]))
        ->assertForbidden();
})->with([
    'el productor no duplica un "Busco" viejo' => ['grower', 'want'],
    'la poscosecha no duplica un "Disponible" viejo' => ['postharvest', 'have'],
]);

test('sí se puede duplicar lo propio del tipo que toca', function () {
    $mine = listingFrom($this->postharvest, ListingKind::Want, [$this->variety]);

    $this->actingAs($this->postharvest)
        ->get(route('listings.create', ['from' => $mine->id]))
        ->assertOk();
});

test('el inicio manda un solo botón y dos pestañas, según el tipo', function () {
    $this->actingAs($this->grower)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) {
            $props = props($page);

            expect($props['publishes'])->toBe('have')
                ->and(array_keys($props['tabs']))->toBe(['for-me', 'mine'])
                // El interruptor "Ver todos" es solo del productor.
                ->and($props['canFilterAll'])->toBeTrue();
        });

    $this->actingAs($this->postharvest)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) {
            $props = props($page);

            expect($props['publishes'])->toBe('want')
                ->and(array_keys($props['tabs']))->toBe(['available', 'mine'])
                ->and($props['canFilterAll'])->toBeFalse();
        });
});

// ── Ofertar ─────────────────────────────────────────────────────────────

test('solo oferta quien debe, en lo que debe', function () {
    $want = listingFrom($this->postharvest, ListingKind::Want, [$this->variety]);
    $have = listingFrom($this->grower, ListingKind::Have, [$this->variety]);

    // Lo que sí: el productor vende en un "Busco", la poscosecha compra
    // en un "Disponible".
    expect($this->grower->can('offer', $want))->toBeTrue()
        ->and($this->postharvest->can('offer', $have))->toBeTrue();

    // Lo que no: cada uno en la publicación del otro tipo.
    expect($this->grower->can('offer', $have))->toBeFalse()
        ->and($this->postharvest->can('offer', $want))->toBeFalse();
});

test('nadie oferta en un "Busco" de un productor', function () {
    // Publicación de antes de la regla: no la oferta ni el otro tipo.
    $stale = listingFrom($this->grower, ListingKind::Want, [$this->variety]);
    $otherGrower = growerUser($this->variety);

    expect($this->postharvest->can('offer', $stale))->toBeFalse()
        ->and($otherGrower->can('offer', $stale))->toBeFalse();

    $this->actingAs($this->postharvest)
        ->post(route('offers.save', $stale), [
            'items' => [[
                'listing_item_id' => $stale->items->first()->id,
                'quantity' => 3,
                'unit' => 'hb',
                'length' => 60,
            ]],
            'delivery' => 'pickup',
        ])
        ->assertForbidden();
});

// ── Avisos ──────────────────────────────────────────────────────────────

/**
 * El aviso de "hay un pedido con tus variedades" existe para avisar a quien
 * puede venderlo. Un "Busco" de un productor no se lo puede vender nadie,
 * asi que no debe mover ni un aviso.
 *
 * Que tampoco se avise a otras poscosechas ya lo cubre NotificationTest.
 */
test('el aviso de nuevo pedido solo sale por "Busco" de poscosechas', function (string $who, bool $avisa) {
    Notification::fake();

    // OJO: el que recibe NO puede ser el mismo que publica. El aviso ya
    // excluye a la empresa propia, asi que con un solo productor el test
    // pasaria igual aunque se quitara la regla: comprobado quitandola.
    $otroProductor = growerUser($this->variety);

    $publisher = $who === 'grower' ? $this->grower : $this->postharvest;
    $listing = listingFrom($publisher, ListingKind::Want, [$this->variety]);

    (new NotifyMatchingGrowers($listing))->handle();

    $avisa
        ? Notification::assertSentTo($otroProductor, MatchingListingPublished::class)
        : Notification::assertNotSentTo($otroProductor, MatchingListingPublished::class);
})->with([
    'de una poscosecha: si avisa' => ['postharvest', true],
    'de un productor: no avisa a nadie' => ['grower', false],
]);

// ── Limpiar lo que quedó de antes ───────────────────────────────────────

test('florea:fix-roles encuentra solo lo que rompe la regla', function () {
    $ok1 = listingFrom($this->postharvest, ListingKind::Want, [$this->variety]);
    $ok2 = listingFrom($this->grower, ListingKind::Have, [$this->variety]);
    $bad = listingFrom($this->grower, ListingKind::Want, [$this->variety]);

    $this->artisan('florea:fix-roles')
        ->expectsOutputToContain('Publicaciones que rompen la regla: 1')
        ->assertSuccessful();

    // Sin --apply no toca nada.
    expect($bad->fresh()->status)->toBe(ListingStatus::Open);

    $this->artisan('florea:fix-roles --apply')
        ->expectsConfirmation('¿Cerrar estas publicaciones y rechazar sus ofertas pendientes?', 'yes')
        ->assertSuccessful();

    expect($bad->fresh()->status)->toBe(ListingStatus::Closed)
        ->and($ok1->fresh()->status)->toBe(ListingStatus::Open)
        ->and($ok2->fresh()->status)->toBe(ListingStatus::Open);
});

test('florea:fix-roles rechaza las ofertas pendientes de lo que cierra', function () {
    $bad = listingFrom($this->grower, ListingKind::Want, [$this->variety]);
    $offer = offerOn($bad, postharvestUser());

    $this->artisan('florea:fix-roles --apply')
        ->expectsConfirmation('¿Cerrar estas publicaciones y rechazar sus ofertas pendientes?', 'yes')
        ->assertSuccessful();

    expect($offer->fresh()->status)->toBe(OfferStatus::Rejected);
});

test('florea:fix-roles no dice nada cuando todo está en orden', function () {
    listingFrom($this->postharvest, ListingKind::Want, [$this->variety]);
    listingFrom($this->grower, ListingKind::Have, [$this->variety]);

    $this->artisan('florea:fix-roles')
        ->expectsOutputToContain('No hay publicaciones que rompan la regla')
        ->assertSuccessful();
});
