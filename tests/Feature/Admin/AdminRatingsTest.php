<?php

use App\Enums\ListingKind;
use App\Models\Deal;
use App\Models\User;
use App\Models\Variety;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->variety = Variety::factory()->create(['name' => 'Explorer']);
});

/**
 * Un trato cerrado con las dos calificaciones puestas.
 *
 * @return array{Deal, User, User}
 */
function ratedDeal(int $buyerScore = 5, int $sellerScore = 5, string $paid = 'yes'): array
{
    $variety = Variety::firstWhere('name', 'Explorer') ?? Variety::factory()->create(['name' => 'Explorer']);
    $buyer = postharvestUser();
    $seller = growerUser($variety);

    $listing = listingFrom($buyer, ListingKind::Want, [$variety]);
    $offer = offerOn($listing, $seller);

    test()->actingAs($buyer)->post(route('offers.accept', $offer), ['payment_term' => 'days_15']);
    $deal = Deal::sole();

    test()->actingAs($buyer)->post(route('ratings.store', $deal), [
        'delivered' => 'yes',
        'on_time' => true,
        'rejected_range' => 'none',
        'score' => $buyerScore,
        'comment' => 'COMENTARIO-DE-LA-POSCOSECHA',
    ]);

    test()->actingAs($seller)->post(route('ratings.store', $deal), [
        'paid_as_agreed' => $paid,
        'honored_agreement' => true,
        'score' => $sellerScore,
        'comment' => 'COMENTARIO-DEL-PRODUCTOR',
    ]);

    return [$deal, $buyer, $seller];
}

test('el admin ve las dos direcciones y los comentarios completos', function () {
    ratedDeal();

    $this->actingAs($this->admin)
        ->get(route('admin.ratings.index'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $json = propsJson($page);
            $rows = props($page)['ratings']['data'];

            expect($rows)->toHaveCount(2)
                // Los comentarios, enteros: esta es la única pantalla donde
                // se leen.
                ->and($json)->toContain('COMENTARIO-DE-LA-POSCOSECHA')
                ->and($json)->toContain('COMENTARIO-DEL-PRODUCTOR');

            // Cada dirección trae lo suyo y nulo lo del otro.
            $delProductor = collect($rows)->firstWhere('fromType', 'Productor');
            $deLaPoscosecha = collect($rows)->firstWhere('fromType', 'Poscosecha');

            expect($delProductor['paidAsAgreed'])->toBe('Sí, como acordamos')
                ->and($delProductor['paymentTerm'])->toBe('A 15 días')
                ->and($delProductor['onTime'])->toBeNull()
                ->and($deLaPoscosecha['onTime'])->toBeTrue()
                ->and($deLaPoscosecha['paidAsAgreed'])->toBeNull();
        });
});

test('se filtra por dirección', function () {
    ratedDeal();

    $this->actingAs($this->admin)
        ->get(route('admin.ratings.index', ['direction' => 'grower_to_postharvest']))
        ->assertInertia(function ($page) {
            $rows = props($page)['ratings']['data'];

            expect($rows)->toHaveCount(1)
                ->and($rows[0]['fromType'])->toBe('Productor');
        });
});

test('se filtra por estrellas bajas', function () {
    ratedDeal(buyerScore: 5, sellerScore: 1);

    $this->actingAs($this->admin)
        ->get(route('admin.ratings.index', ['low' => 1]))
        ->assertInertia(function ($page) {
            $rows = props($page)['ratings']['data'];

            expect($rows)->toHaveCount(1)
                ->and($rows[0]['score'])->toBe(1);
        });
});

test('se filtra por empresa, la califique o la hayan calificado', function () {
    [, , $seller] = ratedDeal();

    $this->actingAs($this->admin)
        ->get(route('admin.ratings.index', ['company_id' => $seller->company_id]))
        ->assertInertia(function ($page) {
            // Las dos: una que dio y otra que recibió.
            expect(props($page)['ratings']['data'])->toHaveCount(2);
        });
});

test('el panel de inicio cuenta las bajas de la semana', function () {
    ratedDeal(buyerScore: 2, sellerScore: 1);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => expect(props($page)['stats']['lowRatingsThisWeek'])->toBe(2));
});

test('una calificación baja de la semana pasada ya no cuenta', function () {
    [$deal] = ratedDeal(buyerScore: 1, sellerScore: 1);

    $deal->ratings()->update(['created_at' => now()->subWeeks(2)]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => expect(props($page)['stats']['lowRatingsThisWeek'])->toBe(0));
});

test('las calificaciones están cerradas a las empresas', function () {
    $this->actingAs(postharvestUser())
        ->get(route('admin.ratings.index'))
        ->assertForbidden();
});

/**
 * Lo que distingue esta pantalla de las de las empresas: aquí hay
 * comentarios. Si se filtran a la app, dos personas que se conocen del
 * cantón acaban discutiendo por una frase.
 */
test('los comentarios no salen en ninguna pantalla de empresa', function () {
    [, $buyer, $seller] = ratedDeal();

    foreach ([$buyer, $seller] as $user) {
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => expect(propsJson($page))
                ->not->toContain('COMENTARIO-DE-LA-POSCOSECHA')
                ->not->toContain('COMENTARIO-DEL-PRODUCTOR'));

        $this->actingAs($user)
            ->get(route('my-offers.index'))
            ->assertInertia(fn ($page) => expect(propsJson($page))
                ->not->toContain('COMENTARIO-DE-LA-POSCOSECHA')
                ->not->toContain('COMENTARIO-DEL-PRODUCTOR'));
    }
});
