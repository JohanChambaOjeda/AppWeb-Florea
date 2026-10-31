<?php

use App\Enums\Delivery;
use App\Enums\ListingKind;
use App\Enums\OfferStatus;
use App\Enums\Unit;
use App\Models\Company;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/*
 * Ayudantes compartidos por toda la suite.
 *
 * Pest carga todos los archivos de test en el mismo proceso, así que estas
 * funciones son globales: definirlas aquí evita tener cinco variantes de
 * "crea un productor" con nombres distintos en cada archivo.
 */

/** Dueño de una poscosecha verificada. Entra directo a la app. */
function postharvestUser(): User
{
    return User::factory()->create([
        'company_id' => Company::factory()->postharvest()->verified(),
    ]);
}

/**
 * Dueño de un productor verificado que siembra las variedades dadas.
 *
 * Siempre conviene pasarle al menos una: un productor sin variedades lo
 * manda el middleware grower.onboarded a "Mis variedades", y entonces los
 * tests fallan con un 302 en vez de llegar a la Policy.
 */
function growerUser(Variety ...$varieties): User
{
    $user = User::factory()->create([
        'company_id' => Company::factory()->grower()->verified(),
    ]);

    foreach ($varieties as $variety) {
        $user->company->varieties()->attach($variety->id, ['lengths' => [60]]);
    }

    return $user;
}

/**
 * Publicación de $owner con una línea por variedad.
 *
 * @param  list<Variety>  $varieties
 * @param  list<int>  $lengths  Largos que aceptan todas las líneas.
 * @param  array<string, mixed>  $attributes
 */
function listingFrom(
    User $owner,
    ListingKind $kind = ListingKind::Want,
    array $varieties = [],
    array $lengths = [60],
    array $attributes = [],
): Listing {
    $listing = Listing::factory()->create([
        'company_id' => $owner->company_id,
        'user_id' => $owner->id,
        'kind' => $kind,
        'needed_by' => today(),
        'expires_at' => $kind->expiresAt(),
        ...$attributes,
    ]);

    foreach ($varieties as $variety) {
        $listing->items()->create([
            'variety_id' => $variety->id,
            'lengths' => $lengths,
            'quantity' => 2,
            'unit' => Unit::Hb,
        ]);
    }

    return $listing->load('items');
}

/**
 * Oferta enviada sobre la primera línea de la publicación.
 *
 * @param  array<string, mixed>  $attributes
 */
function offerOn(Listing $listing, ?User $seller = null, array $attributes = []): Offer
{
    $seller ??= growerUser(Variety::factory()->create());

    $offer = $listing->offers()->create([
        'company_id' => $seller->company_id,
        'user_id' => $seller->id,
        'price_per_stem' => 0.45,
        'delivery' => Delivery::IDeliver,
        'status' => OfferStatus::Sent,
        ...$attributes,
    ]);

    $offer->items()->create([
        'listing_item_id' => $listing->items->first()->id,
        'quantity' => 2,
        'unit' => Unit::Hb,
        'length' => $listing->items->first()->lengths[0],
    ]);

    return $offer;
}

/**
 * Mueve created_at hacia atrás.
 *
 * created_at no está en el #[Fillable] de los modelos, así que create() y
 * update() lo ignoran en silencio y hay que forzarlo.
 */
function backdate(Model $model, int $minutesAgo): void
{
    $model->forceFill(['created_at' => now()->subMinutes($minutesAgo)])->save();
}

/**
 * Las props de Inertia como texto, sin escapar las barras de las URLs.
 *
 * json_encode convierte "wa.me/593..." en "wa.me\/593..." y "Quién" en
 * "Quién", así que sin esto fallan las aserciones sobre enlaces y
 * sobre cualquier texto con tilde, aunque el contenido esté bien.
 */
function propsJson($page): string
{
    return json_encode(
        $page->toArray()['props'],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    ) ?: '';
}

/**
 * @return array<string, mixed>
 */
function props($page): array
{
    return $page->toArray()['props'];
}

/**
 * Los ids de las publicaciones que llegaron a un listado paginado.
 *
 * @return list<int>
 */
function listingIds($page): array
{
    return array_column(props($page)['listings']['data'], 'id');
}
