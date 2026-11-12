<?php

use App\Enums\DeliveryResult;
use App\Enums\ListingKind;
use App\Enums\PaymentTerm;
use App\Enums\RejectedRange;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Rating;
use App\Models\User;
use App\Models\Variety;
use App\Notifications\RatingRequested;
use App\Support\Reputation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Un trato cerrado entre una poscosecha (compra) y un productor (vende),
 * cerrado hace $daysAgo días.
 *
 * @return array{Deal, User, User}
 */
function closedDeal(int $daysAgo = 0): array
{
    $variety = Variety::factory()->create(['name' => 'Explorer']);
    $buyer = postharvestUser();
    $seller = growerUser($variety);

    $listing = listingFrom($buyer, ListingKind::Want, [$variety], [70]);
    $offer = offerOn($listing, $seller);

    $deal = Deal::create([
        'offer_id' => $offer->id,
        'buyer_company_id' => $listing->company_id,
        'seller_company_id' => $offer->company_id,
        'closed_at' => now()->subDays($daysAgo),
    ]);

    return [$deal, $buyer, $seller];
}

/** Lo que manda quien compra: la poscosecha, que recibió la flor. */
function ratingPayload(array $override = []): array
{
    return [
        'delivered' => 'yes',
        'on_time' => true,
        'rejected_range' => 'none',
        'score' => 5,
        ...$override,
    ];
}

/** Lo que manda quien vende: el productor, que cobró. */
function sellerRatingPayload(array $override = []): array
{
    return [
        'paid_as_agreed' => 'yes',
        'honored_agreement' => true,
        'score' => 5,
        ...$override,
    ];
}

/**
 * Un trato nacido como nace de verdad: alguien acepta una oferta.
 *
 * @return array{Deal, User, User}
 */
function acceptedDeal(): array
{
    $variety = Variety::factory()->create(['name' => 'Explorer']);
    $buyer = postharvestUser();
    $seller = growerUser($variety);

    $listing = listingFrom($buyer, ListingKind::Want, [$variety], [70]);
    $offer = offerOn($listing, $seller);

    test()->actingAs($buyer)->post(route('offers.accept', $offer), ['payment_term' => 'days_15']);

    return [Deal::sole(), $buyer, $seller];
}

/**
 * El aviso del día siguiente no puede ser la única puerta a calificar: quien
 * lo descarta se quedaría sin poder hacerlo, y la reputación en blanco. El
 * trato lleva el enlace, y lo ven las dos partes.
 */
test('las dos partes llegan a calificar desde el trato, sin el aviso', function () {
    [$deal, $buyer, $seller] = acceptedDeal();

    // Quien publicó lo ve en su publicación.
    $this->actingAs($buyer)
        ->get(route('listings.show', $deal->offer->listing_id))
        ->assertOk()
        ->assertInertia(function ($page) use ($deal) {
            $offer = collect(props($page)['offers'])->firstWhere('id', $deal->offer_id);

            expect($offer['contact']['dealId'])->toBe($deal->id)
                ->and($offer['contact']['rated'])->toBeFalse();
        });

    // Quien ofertó, en "Mis ofertas".
    $this->actingAs($seller)
        ->get(route('my-offers.index'))
        ->assertOk()
        ->assertInertia(function ($page) use ($deal) {
            $offer = collect(props($page)['offers']['data'])->firstWhere('id', $deal->offer_id);

            expect($offer['contact']['dealId'])->toBe($deal->id)
                ->and($offer['contact']['rated'])->toBeFalse();
        });

    // Y el enlace lleva a una pantalla que de verdad existe.
    $this->actingAs($seller)
        ->get(route('ratings.create', $deal->id))
        ->assertOk();
});

test('quien ya calificó no ve otra vez la invitación', function () {
    [$deal, $buyer] = acceptedDeal();

    $this->actingAs($buyer)
        ->post(route('ratings.store', $deal->id), ratingPayload())
        ->assertSessionHasNoErrors();

    $this->actingAs($buyer)
        ->get(route('listings.show', $deal->offer->listing_id))
        ->assertInertia(function ($page) use ($deal) {
            $offer = collect(props($page)['offers'])->firstWhere('id', $deal->offer_id);

            expect($offer['contact']['rated'])->toBeTrue();
        });
});

test('un tercero no ve el trato ni el enlace de calificar', function () {
    [$deal] = acceptedDeal();

    $this->actingAs(postharvestUser())
        ->get(route('listings.show', $deal->offer->listing_id))
        ->assertOk()
        ->assertInertia(fn ($page) => expect(propsJson($page))
            ->not->toContain('dealId')
            ->not->toContain('"contact"'));
});

test('el comprador califica y su respuesta actualiza el trato', function () {
    [$deal, $buyer, $seller] = closedDeal();

    $this->actingAs($buyer)
        ->post(route('ratings.store', $deal), ratingPayload([
            'delivered' => 'partial',
            'rejected_range' => '10_to_25',
            'score' => 4,
            'comment' => 'Llegó algo abierta.',
        ]))
        ->assertRedirect();

    $rating = Rating::sole();
    $deal->refresh();

    expect($rating->score)->toBe(4)
        ->and($rating->from_company_id)->toBe($buyer->company_id)
        ->and($rating->to_company_id)->toBe($seller->company_id)
        ->and($rating->comment)->toBe('Llegó algo abierta.')
        // El comprador manda sobre el trato (regla acordada).
        ->and($deal->delivered)->toBe(DeliveryResult::Partial)
        ->and((float) $deal->rejected_pct)->toBe(RejectedRange::From10To25->percent());
});

test('el vendedor califica pero no toca el trato', function () {
    [$deal, , $seller] = closedDeal();

    $this->actingAs($seller)
        ->post(route('ratings.store', $deal), sellerRatingPayload())
        ->assertRedirect();

    expect(Rating::sole()->from_company_id)->toBe($seller->company_id)
        // El vendedor no decide si se entregó: eso lo dice quien recibe.
        ->and($deal->refresh()->delivered)->toBeNull()
        ->and($deal->rejected_pct)->toBeNull();
});

test('al vendedor no se le pregunta cuánto se rechazó', function () {
    [$deal, , $seller] = closedDeal();

    $this->actingAs($seller)
        ->get(route('ratings.create', $deal))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('isBuyer', false));

    // Y si lo manda igual, se rechaza.
    $this->actingAs($seller)
        ->post(route('ratings.store', $deal), ratingPayload())
        ->assertSessionHasErrors('rejected_range');
});

test('no se puede calificar dos veces el mismo trato', function () {
    [$deal, $buyer] = closedDeal();

    $this->actingAs($buyer)->post(route('ratings.store', $deal), ratingPayload())->assertRedirect();
    $this->actingAs($buyer)->post(route('ratings.store', $deal), ratingPayload())->assertForbidden();
    $this->actingAs($buyer)->get(route('ratings.create', $deal))->assertForbidden();

    expect(Rating::count())->toBe(1);
});

test('no se puede calificar un trato ajeno', function () {
    [$deal] = closedDeal();
    $ajeno = postharvestUser();

    $this->actingAs($ajeno)->get(route('ratings.create', $deal))->assertForbidden();
    $this->actingAs($ajeno)->post(route('ratings.store', $deal), ratingPayload())->assertForbidden();

    expect(Rating::count())->toBe(0);
});

test('las estrellas van de 1 a 5', function (int $score) {
    [$deal, $buyer] = closedDeal();

    $this->actingAs($buyer)
        ->post(route('ratings.store', $deal), ratingPayload(['score' => $score]))
        ->assertSessionHasErrors('score');
})->with([0, 6, -1]);

test('calificar actualiza el promedio de la empresa', function () {
    $variety = Variety::factory()->create();
    $seller = growerUser($variety);
    $scores = [5, 4, 3];

    foreach ($scores as $score) {
        $buyer = postharvestUser();
        $listing = listingFrom($buyer, ListingKind::Want, [$variety]);
        $offer = offerOn($listing, $seller);

        $deal = Deal::create([
            'offer_id' => $offer->id,
            'buyer_company_id' => $listing->company_id,
            'seller_company_id' => $offer->company_id,
            'closed_at' => now(),
        ]);

        $this->actingAs($buyer)->post(route('ratings.store', $deal), ratingPayload([
            'score' => $score,
            'on_time' => $score > 3,
        ]));
    }

    $company = $seller->company->refresh();

    expect($company->ratings_count)->toBe(3)
        ->and((float) $company->ratings_avg)->toBe(4.0)
        // Dos de tres a tiempo.
        ->and($company->on_time_pct)->toBe(67);
});

test('"Nuevo en Florea" con menos de 3 calificaciones', function () {
    $variety = Variety::factory()->create();
    $seller = growerUser($variety);

    expect(Reputation::of($seller->company))
        ->toHaveKey('isNew', true)
        ->toHaveKey('stars', null);

    // Con dos calificaciones sigue siendo nuevo.
    $seller->company->forceFill(['ratings_count' => 2, 'ratings_avg' => 5])->save();

    expect(Reputation::of($seller->company->refresh()))->toHaveKey('isNew', true);

    // Con tres ya se muestra el promedio.
    $seller->company->forceFill(['ratings_count' => 3, 'ratings_avg' => 4.5])->save();

    expect(Reputation::of($seller->company->refresh()))
        ->toHaveKey('isNew', false)
        ->toHaveKey('stars', 4.5);
});

test('la reputación viaja en las tarjetas pero los comentarios no', function () {
    $variety = Variety::factory()->create();

    // Regla 8: la poscosecha mira la flor que ofrecen los productores, así
    // que la reputación que tiene que ver en la tarjeta es la del productor.
    $viewer = postharvestUser();
    $seller = growerUser($variety);
    listingFrom($seller, ListingKind::Have, [$variety]);

    // Un comentario que no debe salir de la base.
    $closed = listingFrom(postharvestUser(), ListingKind::Want, [$variety]);
    $deal = Deal::create([
        'offer_id' => offerOn($closed, $seller)->id,
        'buyer_company_id' => $closed->company_id,
        'seller_company_id' => $seller->company_id,
        'closed_at' => now(),
    ]);
    $deal->ratings()->create([
        'from_company_id' => $deal->buyer_company_id,
        'to_company_id' => $seller->company_id,
        'score' => 5,
        'on_time' => true,
        'comment' => 'ESTE-COMENTARIO-ES-PRIVADO',
    ]);
    $seller->company->recomputeReputation();

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) {
            $json = propsJson($page);

            expect($json)->toContain('reputation')
                // Los comentarios solo los ve el admin.
                ->not->toContain('ESTE-COMENTARIO-ES-PRIVADO');
        });
});

test('el recordatorio llega al día siguiente y otra vez a los 3 días', function () {
    Notification::fake();

    [$deal, $buyer, $seller] = closedDeal(daysAgo: 1);

    // Primer recordatorio: a las dos partes.
    $this->artisan('ratings:request')->assertSuccessful();

    Notification::assertSentToTimes($buyer, RatingRequested::class, 1);
    Notification::assertSentToTimes($seller, RatingRequested::class, 1);

    // Al día siguiente todavía no toca el segundo.
    $this->artisan('ratings:request');
    Notification::assertSentToTimes($buyer, RatingRequested::class, 1);

    // A los 3 días, el segundo y último.
    $deal->forceFill(['closed_at' => now()->subDays(3)])->save();
    $this->artisan('ratings:request');
    Notification::assertSentToTimes($buyer, RatingRequested::class, 2);

    // No hay tercero.
    $deal->forceFill(['closed_at' => now()->subDays(10)])->save();
    $this->artisan('ratings:request');
    Notification::assertSentToTimes($buyer, RatingRequested::class, 2);
});

test('a quien ya calificó no se le insiste', function () {
    Notification::fake();

    [$deal, $buyer, $seller] = closedDeal(daysAgo: 1);

    $this->actingAs($buyer)->post(route('ratings.store', $deal), ratingPayload());

    $this->artisan('ratings:request');

    Notification::assertNotSentTo($buyer, RatingRequested::class);
    // Al vendedor sí, que no ha contestado.
    Notification::assertSentTo($seller, RatingRequested::class);
});

test('un trato recién cerrado no genera recordatorio', function () {
    Notification::fake();

    closedDeal(daysAgo: 0);

    $this->artisan('ratings:request');

    Notification::assertNothingSent();
});

test('calificar borra el recordatorio pendiente', function () {
    [$deal, $buyer] = closedDeal(daysAgo: 1);

    $this->artisan('ratings:request');

    expect(DB::table('rating_reminders')->where('company_id', $buyer->company_id)->count())->toBe(1);

    $this->actingAs($buyer)->post(route('ratings.store', $deal), ratingPayload());

    expect(DB::table('rating_reminders')->where('company_id', $buyer->company_id)->count())->toBe(0);
});

test('aceptar una oferta cuenta el trato para las dos empresas', function () {
    $variety = Variety::factory()->create();
    $publisher = postharvestUser();
    $listing = listingFrom($publisher, ListingKind::Want, [$variety]);
    $offer = offerOn($listing);

    $this->actingAs($publisher)->post(route('offers.accept', $offer), ['payment_term' => 'days_15']);

    expect($publisher->company->refresh()->deals_count)->toBe(1)
        ->and(Company::find($offer->company_id)->deals_count)->toBe(1);
});

/**
 * El plazo de pago se acuerda dentro de la app, no por WhatsApp. Lo declara
 * siempre la poscosecha, que es quien paga, y el productor lo ve ANTES de
 * que el trato quede cerrado.
 */
test('la poscosecha elige el plazo al aceptar un "Busco" suyo', function () {
    $variety = Variety::factory()->create();
    $buyer = postharvestUser();
    $seller = growerUser($variety);

    $listing = listingFrom($buyer, ListingKind::Want, [$variety]);
    $offer = offerOn($listing, $seller);

    $this->actingAs($buyer)
        ->post(route('offers.accept', $offer), ['payment_term' => 'days_30'])
        ->assertSessionHasNoErrors();

    expect(Deal::sole()->payment_term)->toBe(PaymentTerm::Days30);
});

test('aceptar sin decir el plazo no cierra el trato', function () {
    $variety = Variety::factory()->create();
    $buyer = postharvestUser();

    $listing = listingFrom($buyer, ListingKind::Want, [$variety]);
    $offer = offerOn($listing, growerUser($variety));

    $this->actingAs($buyer)
        ->post(route('offers.accept', $offer))
        ->assertSessionHasErrors('payment_term');

    expect(Deal::count())->toBe(0);
});

test('en un "Disponible" el plazo viene de la oferta y pasa al trato', function () {
    $variety = Variety::factory()->create();
    $seller = growerUser($variety);
    $buyer = postharvestUser();

    $listing = listingFrom($seller, ListingKind::Have, [$variety]);

    // La poscosecha compra, así que deja dicho el plazo en su oferta.
    $this->actingAs($buyer)->post(route('offers.save', $listing), [
        'items' => [[
            'listing_item_id' => $listing->items->first()->id,
            'quantity' => 2, 'unit' => 'hb', 'length' => 60,
        ]],
        'delivery' => 'pickup',
        'payment_term' => 'days_8',
    ])->assertSessionHasNoErrors();

    // El productor acepta sin tener que decir nada: ya lo sabía.
    $this->actingAs($seller)
        ->post(route('offers.accept', $listing->offers()->sole()))
        ->assertSessionHasNoErrors();

    expect(Deal::sole()->payment_term)->toBe(PaymentTerm::Days8);
});

test('cada uno contesta lo suyo y lo del otro se rechaza', function () {
    [$deal, $buyer, $seller] = closedDeal();

    // La poscosecha no reporta el pago: lo reporta quien cobra.
    $this->actingAs($buyer)
        ->post(route('ratings.store', $deal), ratingPayload(['paid_as_agreed' => 'yes']))
        ->assertSessionHasErrors('paid_as_agreed');

    // Y el productor no reporta la entrega.
    $this->actingAs($seller)
        ->post(route('ratings.store', $deal), sellerRatingPayload(['delivered' => 'yes']))
        ->assertSessionHasErrors('delivered');
});

test('el productor califica el pago y mueve el % de la poscosecha', function () {
    $variety = Variety::factory()->create();
    $buyer = postharvestUser();

    // Tres tratos: dos pagados como se acordó y uno no.
    foreach (['yes', 'yes', 'no'] as $answer) {
        $seller = growerUser($variety);
        $listing = listingFrom($buyer, ListingKind::Want, [$variety]);
        $offer = offerOn($listing, $seller);

        $this->actingAs($buyer)->post(route('offers.accept', $offer), ['payment_term' => 'days_15']);

        $this->actingAs($seller)->post(
            route('ratings.store', Deal::where('offer_id', $offer->id)->sole()),
            sellerRatingPayload(['paid_as_agreed' => $answer, 'score' => 4]),
        )->assertSessionHasNoErrors();
    }

    $reputation = Reputation::of($buyer->company->fresh());

    expect($reputation['paidPct'])->toBe(67)
        ->and($reputation['onTimePct'])->toBeNull();
});

test('"todavía no" no cuenta ni a favor ni en contra', function () {
    $variety = Variety::factory()->create();
    $buyer = postharvestUser();

    // Un "sí" y dos "todavía no": el porcentaje es 100, no 33.
    foreach (['yes', 'not_yet', 'not_yet'] as $answer) {
        $seller = growerUser($variety);
        $listing = listingFrom($buyer, ListingKind::Want, [$variety]);
        $offer = offerOn($listing, $seller);

        $this->actingAs($buyer)->post(route('offers.accept', $offer), ['payment_term' => 'days_30']);

        $this->actingAs($seller)->post(
            route('ratings.store', Deal::where('offer_id', $offer->id)->sole()),
            sellerRatingPayload(['paid_as_agreed' => $answer]),
        );
    }

    expect($buyer->company->fresh()->paid_pct)->toBe(100);
});
