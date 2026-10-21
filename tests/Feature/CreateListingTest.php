<?php

use App\Enums\ListingKind;
use App\Enums\ListingStatus;
use App\Enums\Unit;
use App\Models\Company;
use App\Models\Listing;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->publisher = postharvestUser();
    $this->freedom = Variety::factory()->create(['name' => 'Freedom']);
    $this->explorer = Variety::factory()->create(['name' => 'Explorer']);
    $this->pinkFloyd = Variety::factory()->create(['name' => 'Pink Floyd']);
});

/**
 * Payload del formulario de publicación, con los valores por defecto que
 * manda la pantalla.
 *
 * @param  array<string, mixed>  $override
 * @return array<string, mixed>
 */
function listingPayload(array $items, array $override = []): array
{
    return [
        'kind' => 'want',
        'needed_by' => today()->toDateString(),
        'items' => $items,
        ...$override,
    ];
}

test('se crea un "Busco" con varias líneas y varios largos, y vence a las 48 h', function () {
    $this->travelTo(now()->startOfMinute());

    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([
            // Varios largos en una línea: "60/50".
            ['variety_id' => $this->freedom->id, 'lengths' => [60, 50], 'quantity' => 5, 'unit' => 'hb'],
            // Tres largos y en mallas.
            ['variety_id' => $this->explorer->id, 'lengths' => [70, 60, 80], 'quantity' => 3, 'unit' => 'mesh'],
            // Sin cantidad: es opcional.
            ['variety_id' => $this->pinkFloyd->id, 'lengths' => [60], 'quantity' => null, 'unit' => 'hb'],
        ], [
            'destination' => 'russia',
            'cut_point' => 'closed',
            'notes' => 'Para armar un pedido de exportación.',
        ]))
        ->assertRedirect();

    $listing = Listing::sole();

    expect($listing->kind)->toBe(ListingKind::Want)
        ->and($listing->status)->toBe(ListingStatus::Open)
        ->and($listing->company_id)->toBe($this->publisher->company_id)
        ->and($listing->destination->value)->toBe('russia')
        ->and($listing->cut_point->value)->toBe('closed')
        ->and($listing->notes)->toBe('Para armar un pedido de exportación.')
        // Regla 7: un "Busco" vive 48 horas.
        ->and($listing->expires_at->equalTo(now()->addHours(48)))->toBeTrue();

    $items = $listing->items()->orderBy('id')->get();

    expect($items)->toHaveCount(3);

    // Los largos se guardan ordenados de menor a mayor y sin repetidos.
    expect($items[0]->lengths)->toEqual([50, 60])
        ->and($items[0]->unit)->toBe(Unit::Hb)
        ->and((float) $items[0]->quantity)->toBe(5.0);

    expect($items[1]->lengths)->toEqual([60, 70, 80])
        ->and($items[1]->unit)->toBe(Unit::Mesh);

    expect($items[2]->lengths)->toEqual([60])
        ->and($items[2]->quantity)->toBeNull();
});

test('un "Disponible" vence a las 24 h', function () {
    $this->travelTo(now()->startOfMinute());

    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload(
            [['variety_id' => $this->freedom->id, 'lengths' => [60], 'quantity' => 10, 'unit' => 'hb']],
            ['kind' => 'have'],
        ))
        ->assertRedirect();

    $listing = Listing::sole();

    expect($listing->kind)->toBe(ListingKind::Have)
        ->and($listing->expires_at->equalTo(now()->addHours(24)))->toBeTrue();
});

test('tras publicar se va al detalle de la publicación', function () {
    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload(
            [['variety_id' => $this->freedom->id, 'lengths' => [60], 'quantity' => 2, 'unit' => 'hb']],
        ))
        ->assertRedirect(route('listings.show', Listing::sole()));
});

test('no se puede repetir la misma variedad con los mismos largos', function () {
    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([
            ['variety_id' => $this->freedom->id, 'lengths' => [60, 50], 'quantity' => 5, 'unit' => 'hb'],
            // Los mismos largos, en otro orden: sigue siendo la misma línea.
            ['variety_id' => $this->freedom->id, 'lengths' => [50, 60], 'quantity' => 3, 'unit' => 'hb'],
        ]))
        ->assertSessionHasErrors('items.1.variety_id');

    expect(Listing::count())->toBe(0);
});

test('dos líneas pueden compartir el mismo largo', function () {
    // El caso más común del sector: casi todo se pide en 60.
    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([
            ['variety_id' => $this->pinkFloyd->id, 'lengths' => [60], 'quantity' => 2, 'unit' => 'hb'],
            ['variety_id' => $this->explorer->id, 'lengths' => [60], 'quantity' => 3, 'unit' => 'hb'],
            ['variety_id' => $this->freedom->id, 'lengths' => [60, 50], 'quantity' => 1, 'unit' => 'hb'],
        ]))
        ->assertSessionHasNoErrors();

    expect(Listing::sole()->items)->toHaveCount(3);
});

test('un largo repetido dentro de una línea se limpia en vez de fallar', function () {
    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([
            ['variety_id' => $this->freedom->id, 'lengths' => [60, 60, 50], 'quantity' => 2, 'unit' => 'hb'],
        ]))
        ->assertSessionHasNoErrors();

    expect(Listing::sole()->items()->sole()->lengths)->toEqual([50, 60]);
});

test('la misma variedad con largos distintos sí vale', function () {
    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([
            ['variety_id' => $this->freedom->id, 'lengths' => [60], 'quantity' => 5, 'unit' => 'hb'],
            ['variety_id' => $this->freedom->id, 'lengths' => [50], 'quantity' => 5, 'unit' => 'hb'],
        ]))
        ->assertSessionHasNoErrors();

    expect(Listing::sole()->items)->toHaveCount(2);
});

test('hace falta al menos una línea', function () {
    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([]))
        ->assertSessionHasErrors('items');

    expect(Listing::count())->toBe(0);
});

test('cada línea necesita al menos un largo', function () {
    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([
            ['variety_id' => $this->freedom->id, 'lengths' => [], 'quantity' => 5, 'unit' => 'hb'],
        ]))
        ->assertSessionHasErrors('items.0.lengths');
});

test('se admite un largo especial dentro del rango, y se rechaza fuera', function () {
    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([
            ['variety_id' => $this->freedom->id, 'lengths' => [120], 'quantity' => 1, 'unit' => 'hb'],
        ]))
        ->assertSessionHasNoErrors();

    expect(Listing::sole()->items()->sole()->lengths)->toEqual([120]);

    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([
            ['variety_id' => $this->freedom->id, 'lengths' => [200], 'quantity' => 1, 'unit' => 'hb'],
        ]))
        ->assertSessionHasErrors('items.0.lengths.0');
});

test('no se puede publicar con una variedad desactivada', function () {
    $inactiva = Variety::factory()->create(['is_active' => false]);

    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload([
            ['variety_id' => $inactiva->id, 'lengths' => [60], 'quantity' => 1, 'unit' => 'hb'],
        ]))
        ->assertSessionHasErrors('items.0.variety_id');
});

test('la fecha es obligatoria y no puede ser de ayer', function () {
    $base = [['variety_id' => $this->freedom->id, 'lengths' => [60], 'quantity' => 1, 'unit' => 'hb']];

    $this->actingAs($this->publisher)
        ->post(route('listings.store'), ['kind' => 'want', 'items' => $base])
        ->assertSessionHasErrors('needed_by');

    $this->actingAs($this->publisher)
        ->post(route('listings.store'), listingPayload($base, [
            'needed_by' => today()->subDay()->toDateString(),
        ]))
        ->assertSessionHasErrors('needed_by');

    expect(Listing::count())->toBe(0);
});

test('una empresa sin verificar no puede publicar', function () {
    $sinVerificar = User::factory()->unverifiedCompany()->create();

    $this->actingAs($sinVerificar)
        ->get(route('listings.create'))
        ->assertRedirect(route('account.pending'));

    $this->actingAs($sinVerificar)
        ->post(route('listings.store'), listingPayload([
            ['variety_id' => $this->freedom->id, 'lengths' => [60], 'quantity' => 1, 'unit' => 'hb'],
        ]))
        ->assertRedirect(route('account.pending'));

    expect(Listing::count())->toBe(0);
});

test('el admin no publica', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('listings.create'))->assertForbidden();
});

test('duplicar abre el formulario con las mismas líneas y sin fecha', function () {
    $original = listingFrom($this->publisher, ListingKind::Want, [$this->freedom, $this->explorer], [70, 60], [
        'destination' => 'usa',
        'notes' => 'Se repite cada semana.',
    ]);

    $this->actingAs($this->publisher)
        ->get(route('listings.create', ['from' => $original->id]))
        ->assertOk()
        ->assertInertia(function ($page) use ($original) {
            $prefill = props($page)['prefill'];

            expect(props($page)['duplicatedFrom'])->toBe($original->id);
            expect($prefill['items'])->toHaveCount(2);
            expect($prefill['items'][0]['lengths'])->toEqual([70, 60]);
            expect($prefill['destination'])->toBe('usa');
            expect($prefill['notes'])->toBe('Se repite cada semana.');
            // La fecha no se copia: un pedido repetido es para otro día.
            expect($prefill)->not->toHaveKey('needed_by');
        });
});

test('duplicar vale sobre una propia vencida o cerrada', function (string $status) {
    $original = listingFrom($this->publisher, ListingKind::Want, [$this->freedom]);
    $original->update(['status' => $status]);

    $this->actingAs($this->publisher)
        ->get(route('listings.create', ['from' => $original->id]))
        ->assertOk();
})->with(['closed', 'expired']);

test('no se puede duplicar la publicación de otra empresa', function () {
    $ajena = listingFrom(postharvestUser(), ListingKind::Want, [$this->freedom]);

    $this->actingAs($this->publisher)
        ->get(route('listings.create', ['from' => $ajena->id]))
        ->assertForbidden();
});

test('un productor sin variedades no llega al formulario', function () {
    $sinVariedades = User::factory()->create([
        'company_id' => Company::factory()->grower()->verified(),
    ]);

    $this->actingAs($sinVariedades)
        ->get(route('listings.create'))
        ->assertRedirect(route('my-varieties.edit'));
});
