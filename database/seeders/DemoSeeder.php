<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Enums\ListingKind;
use App\Enums\ListingStatus;
use App\Enums\Unit;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Listing;
use App\Models\User;
use App\Models\Variety;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración. Solo corre en local; nunca en producción.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'florea2026';

    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command->warn('DemoSeeder solo corre en local. Omitido.');

            return;
        }

        DB::transaction(function () {
            $admin = User::where('role', UserRole::Admin)->first();

            [$ph1, $ph2, $ph3] = [
                $this->company(CompanyType::Postharvest, 'Poscosecha Rosales del Norte', 'Tabacundo', 'María Quishpe', '0990000101', $admin),
                $this->company(CompanyType::Postharvest, 'Poscosecha Valle Florido', 'La Esperanza', 'Carla Andrango', '0990000102', $admin),
                $this->company(CompanyType::Postharvest, 'Poscosecha Cumbre Andina', 'Tupigachi', 'Diana Cacuango', '0990000103', $admin),
            ];

            [$gr1, $gr2, $gr3] = [
                $this->company(CompanyType::Grower, 'Finca San Isidro', 'Tabacundo', 'Luis Farinango', '0990000201', $admin),
                $this->company(CompanyType::Grower, 'Finca El Rosal', 'La Esperanza', 'Segundo Imbaquingo', '0990000202', $admin),
                $this->company(CompanyType::Grower, 'Finca Santa Clara', 'Malchinguí', 'Rosa Tituaña', '0990000203', $admin),
            ];

            // Productor 4 sin variedades: sirve para probar la pantalla "Mis variedades".
            $this->company(CompanyType::Grower, 'Finca Los Cipreses', 'Tocachi', 'Jorge Catucuamba', '0990000204', $admin);

            $this->varieties($gr1, ['Brighton' => [60], 'Hermosa' => [50], 'Vendela' => [70], 'Topaz' => [60]]);
            $this->varieties($gr2, ['Pink Floyd' => [50, 60], 'Explorer' => [60, 70, 80], 'Freedom' => [50, 60]]);
            $this->varieties($gr3, ['Mondial' => [50, 60], 'Quicksand' => [60], 'Shimmer' => [60]]);

            $mallas = fn (string $name) => [$name, [50, 60, 70], null, Unit::Mesh];

            $this->listing($ph1, ListingKind::Want, [
                $mallas('Be Sweet'), $mallas('Playa Blanca'), $mallas('Candlelight'), $mallas('Pink Mondial'),
                $mallas('Quicksand'), $mallas('Explorer'), $mallas('Toffee'), $mallas('Fortuner'),
            ], ['needed_by' => today()->addDay(), 'notes' => 'Para hoy o mañana, en mallas.']);

            $this->listing($ph2, ListingKind::Have, [
                ['Explorer', [60, 70, 80], null, null],
                ['Pink Floyd', [50, 60], null, null],
                ['Mandala', [60, 50], null, null],
                ['Playa Blanca', [40, 50, 60], null, null],
            ]);

            $this->listing($gr1, ListingKind::Have, [
                ['Brighton', [60], 10, Unit::Hb],
                ['Hermosa', [50], 15, Unit::Hb],
                ['Vendela', [70], 8, Unit::Hb],
                ['Topaz', [60], 12, Unit::Hb],
            ]);

            $this->listing($ph3, ListingKind::Want, [
                ['Pink Floyd', [60], 2, Unit::Hb],
                ['Explorer', [70], 3, Unit::Hb],
                ['Mondial', [50], 1, Unit::Hb],
                ['Paloma', [60], 2, Unit::Hb],
            ]);

            $this->listing($ph1, ListingKind::Want, [
                ['Freedom', [60], 5, Unit::Hb],
                ['Freedom', [50], 5, Unit::Hb],
                ['Deep Purple', [60], 2, Unit::Hb],
                ['White Chocolate', [70], 1, Unit::Hb],
            ]);

            $this->listing($ph2, ListingKind::Want, [
                ['Toffee', [50, 60], 2, Unit::Hb],
                ['Quicksand', [60], 4, Unit::Hb],
                ['Shimmer', [60], 3, Unit::Hb],
            ]);

            $this->listing($ph3, ListingKind::Want, [
                ['Hard Rock', [60, 50], 1, Unit::Hb],
                ['Nena', [50], 2, Unit::Hb],
                ['Voodoo', [60], 3, Unit::Hb],
                ['Geraldine', [60], 2, Unit::Hb],
            ]);
        });
    }

    private function company(CompanyType $type, string $name, string $zone, string $owner, string $phone, ?User $admin): Company
    {
        $company = Company::create([
            'type' => $type,
            'name' => $name,
            'tax_id' => fake()->unique()->numerify('17########001'),
            'zone_id' => Zone::where('name', $zone)->value('id'),
            'verified_at' => now(),
            'verified_by' => $admin?->id,
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => $owner,
            'phone' => '+593'.substr($phone, 1),
            'role' => UserRole::Owner,
            'password' => self::PASSWORD,
        ]);

        return $company;
    }

    /**
     * @param  array<string, list<int>>  $varieties
     */
    private function varieties(Company $company, array $varieties): void
    {
        foreach ($varieties as $name => $lengths) {
            $company->varieties()->attach(Variety::where('name', $name)->value('id'), ['lengths' => $lengths]);
        }
    }

    /**
     * @param  list<array{0: string, 1: list<int>, 2: int|null, 3: Unit|null}>  $items
     * @param  array<string, mixed>  $attributes
     */
    private function listing(Company $company, ListingKind $kind, array $items, array $attributes = []): Listing
    {
        $listing = $company->listings()->create([
            'user_id' => $company->owner->id,
            'kind' => $kind,
            'status' => ListingStatus::Open,
            'expires_at' => now()->addDays(2)->endOfDay(),
            ...$attributes,
        ]);

        foreach ($items as [$variety, $lengths, $quantity, $unit]) {
            $listing->items()->create([
                'variety_id' => Variety::where('name', $variety)->value('id'),
                'lengths' => $lengths,
                'quantity' => $quantity,
                'unit' => $unit,
            ]);
        }

        return $listing;
    }
}
