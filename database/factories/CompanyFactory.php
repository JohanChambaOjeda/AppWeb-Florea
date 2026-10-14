<?php

namespace Database\Factories;

use App\Enums\CompanyType;
use App\Enums\Plan;
use App\Models\Company;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Por defecto la empresa queda sin verificar, como recién registrada.
 *
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(CompanyType::cases()),
            'name' => 'Finca '.fake()->unique()->lastName(),
            'tax_id' => fake()->numerify('17########001'),
            'zone_id' => Zone::factory(),
            'address_reference' => null,
            'verified_at' => null,
            'plan' => Plan::Pilot,
        ];
    }

    public function postharvest(): static
    {
        return $this->state(fn () => [
            'type' => CompanyType::Postharvest,
            'name' => 'Poscosecha '.fake()->unique()->lastName(),
        ]);
    }

    public function grower(): static
    {
        return $this->state(fn () => ['type' => CompanyType::Grower]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verified_at' => now()]);
    }
}
