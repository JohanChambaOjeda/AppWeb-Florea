<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyVariety;
use App\Models\Variety;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyVariety>
 */
class CompanyVarietyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->grower()->verified(),
            'variety_id' => Variety::factory(),
            'lengths' => fake()->randomElements(config('florea.standard_lengths'), 2),
        ];
    }
}
