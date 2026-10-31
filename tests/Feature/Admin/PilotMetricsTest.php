<?php

use App\Enums\ListingKind;
use App\Models\Feedback;
use App\Models\Listing;
use App\Models\User;
use App\Models\Variety;
use App\Support\PilotMetrics;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // Un martes cualquiera, para que la semana no cambie a mitad del test.
    $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));

    $this->admin = User::factory()->admin()->create();
    $this->variety = Variety::factory()->create(['name' => 'Explorer']);
});

/**
 * Un "Busco" publicado hace $publishedAgo horas que recibió su primera
 * oferta $waitHours horas después. Con null, nadie ofertó.
 */
function wantWithWait(int $publishedAgoHours, ?float $waitHours): Listing
{
    $listing = listingFrom(postharvestUser(), ListingKind::Want, [
        Variety::firstWhere('name', 'Explorer') ?? Variety::factory()->create(['name' => 'Explorer']),
    ]);

    $publishedAt = now()->subHours($publishedAgoHours);
    $listing->forceFill(['created_at' => $publishedAt])->save();

    if ($waitHours !== null) {
        $offer = offerOn($listing);
        $offer->forceFill([
            'created_at' => $publishedAt->copy()->addMinutes((int) round($waitHours * 60)),
        ])->save();
    }

    return $listing;
}

test('el porcentaje en menos de 4 horas cuenta TODOS los "Busco"', function () {
    // Dos rápidos, uno lento, uno sin ofertas.
    wantWithWait(publishedAgoHours: 20, waitHours: 1);
    wantWithWait(publishedAgoHours: 20, waitHours: 3.5);
    wantWithWait(publishedAgoHours: 20, waitHours: 9);
    wantWithWait(publishedAgoHours: 20, waitHours: null);

    $fast = PilotMetrics::forWeek(now())['fast'];

    // 2 de 4 = 50%. El que nadie contestó cuenta en el denominador: es un
    // pedido que Florea no resolvió, y esconderlo maquillaría el número.
    expect($fast['total'])->toBe(4)
        ->and($fast['withinHours'])->toBe(2)
        ->and($fast['pct'])->toBe(50)
        ->and($fast['goalMet'])->toBeTrue();
});

test('justo en 4 horas cuenta como rápido, y un minuto más no', function () {
    wantWithWait(publishedAgoHours: 20, waitHours: 4);

    expect(PilotMetrics::forWeek(now())['fast']['pct'])->toBe(100);
});

test('por debajo de la meta se marca como no cumplida', function () {
    wantWithWait(publishedAgoHours: 20, waitHours: 1);
    wantWithWait(publishedAgoHours: 20, waitHours: 9);
    wantWithWait(publishedAgoHours: 20, waitHours: 9);

    $fast = PilotMetrics::forWeek(now())['fast'];

    expect($fast['pct'])->toBe(33)->and($fast['goalMet'])->toBeFalse();
});

test('la mediana aguanta mejor un caso raro que el promedio', function () {
    // 1 h, 2 h, 3 h y uno de 20 h que descoloca el promedio.
    // 30 horas antes de un martes sigue siendo lunes: misma semana.
    foreach ([1, 2, 3, 20] as $hours) {
        wantWithWait(publishedAgoHours: 30, waitHours: $hours);
    }

    $first = PilotMetrics::forWeek(now())['firstOffer'];

    expect($first['measured'])->toBe(4)
        // Mediana de 60, 120, 180, 1200 → (120+180)/2 = 150 min.
        ->and($first['medianMinutes'])->toBe(150)
        // El promedio se dispara a 390 min por culpa del de 20 h.
        ->and($first['averageMinutes'])->toBe(390);
});

test('sin datos la métrica es nula y no revienta', function () {
    $week = PilotMetrics::forWeek(now());

    expect($week['fast']['pct'])->toBeNull()
        ->and($week['fast']['goalMet'])->toBeFalse()
        ->and($week['firstOffer']['medianMinutes'])->toBeNull()
        ->and($week['firstOffer']['averageMinutes'])->toBeNull()
        ->and($week['listings'])->toBe(0);
});

test('cada semana cuenta lo suyo', function () {
    wantWithWait(publishedAgoHours: 2, waitHours: 1);

    expect(PilotMetrics::forWeek(now())['wants'])->toBe(1);
    // La semana pasada no tuvo nada.
    expect(PilotMetrics::forWeek(now()->subWeek())['wants'])->toBe(0);
});

test('las empresas activas cuentan publicar, ofertar y entrar', function () {
    $publica = postharvestUser();
    listingFrom($publica, ListingKind::Want, [$this->variety]);

    $oferta = growerUser($this->variety);
    offerOn(listingFrom(postharvestUser(), ListingKind::Want, [$this->variety]), $oferta);

    // Esta solo entró a mirar.
    $entra = growerUser($this->variety);
    $entra->forceFill(['last_seen_at' => now()])->save();

    // Y esta no hizo nada.
    growerUser($this->variety);

    $active = PilotMetrics::forWeek(now())['activeCompanies'];

    // 2 poscosechas que publicaron + el que ofertó + el que entró.
    expect($active['postharvest'])->toBe(2)
        ->and($active['growers'])->toBe(2)
        ->and($active['total'])->toBe(4);
});

test('entrar a la app marca last_seen_at', function () {
    $user = growerUser($this->variety);

    expect($user->last_seen_at)->toBeNull();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->refresh()->last_seen_at)->not->toBeNull();
});

test('la pantalla de métricas muestra la semana y la meta', function () {
    wantWithWait(publishedAgoHours: 5, waitHours: 1);

    $this->actingAs($this->admin)
        ->get(route('admin.metrics.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/metrics/index')
            ->where('goal.hours', 4)
            ->where('goal.pct', 50)
            ->where('week.fast.pct', 100)
            ->where('week.wants', 1));
});

test('se puede mirar una semana anterior', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.metrics.index', ['week' => now()->subWeek()->toDateString()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'selected',
            now()->subWeek()->startOfWeek()->toDateString(),
        ));
});

test('una semana inválida cae en la actual sin romperse', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.metrics.index', ['week' => 'no-es-fecha']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'selected',
            now()->startOfWeek()->toDateString(),
        ));
});

test('el CSV sale con cabeceras y una fila por semana', function () {
    wantWithWait(publishedAgoHours: 5, waitHours: 1);

    $response = $this->actingAs($this->admin)->get(route('admin.metrics.export'));

    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('% <4h')
        ->toContain('Empresas activas')
        // BOM para que Excel en Windows no rompa las tildes.
        ->toStartWith("\xEF\xBB\xBF");

    // Cabecera + 8 semanas.
    expect(substr_count(trim($csv), "\n"))->toBe(8);
});

test('las métricas están cerradas a las empresas', function (string $route) {
    $this->actingAs(postharvestUser())->get(route($route))->assertForbidden();
})->with(['admin.metrics.index', 'admin.metrics.export', 'admin.feedback.index']);

test('cualquiera puede reportar que algo no funcionó', function () {
    $user = growerUser($this->variety);

    $this->actingAs($user)
        ->post(route('feedback.store'), [
            'message' => 'No pude subir la foto desde el celular',
            'screen' => 'offers/edit',
        ])
        ->assertRedirect();

    $item = Feedback::sole();

    expect($item->message)->toBe('No pude subir la foto desde el celular')
        ->and($item->screen)->toBe('offers/edit')
        ->and($item->user_id)->toBe($user->id)
        ->and($item->company_id)->toBe($user->company_id)
        ->and($item->isResolved())->toBeFalse();
});

test('el comentario no puede ir vacío', function () {
    $this->actingAs(growerUser($this->variety))
        ->post(route('feedback.store'), ['message' => ''])
        ->assertSessionHasErrors('message');
});

test('el admin ve los comentarios y los marca resueltos', function () {
    $item = Feedback::create(['message' => 'Algo pasó', 'screen' => 'dashboard']);

    $this->actingAs($this->admin)
        ->get(route('admin.feedback.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('pending', 1)->count('items.data', 1));

    $this->actingAs($this->admin)
        ->post(route('admin.feedback.toggle', $item))
        ->assertRedirect();

    expect($item->refresh()->isResolved())->toBeTrue();

    // Resuelto desaparece de la lista por defecto.
    $this->actingAs($this->admin)
        ->get(route('admin.feedback.index'))
        ->assertInertia(fn ($page) => $page->where('pending', 0)->count('items.data', 0));
});

test('el admin cambia el plan de una empresa con una nota', function () {
    $company = postharvestUser()->company;

    $this->actingAs($this->admin)
        ->post(route('admin.companies.plan', $company), [
            'plan' => 'paid',
            'plan_until' => '2027-01-31',
            'plan_note' => 'Paga desde enero',
        ])
        ->assertRedirect();

    $company->refresh();

    expect($company->plan->value)->toBe('paid')
        ->and($company->plan_until->toDateString())->toBe('2027-01-31')
        ->and($company->plan_note)->toBe('Paga desde enero');
});

test('las publicaciones abiertas traen el mensaje para reenviar', function () {
    listingFrom(postharvestUser(), ListingKind::Want, [$this->variety]);

    $this->actingAs($this->admin)
        ->get(route('admin.listings.index'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $row = props($page)['listings']['data'][0];

            expect($row['whatsappText'])
                ->toContain('Explorer')
                ->toContain('para hoy')
                ->toContain('/listings/');
        });
});
