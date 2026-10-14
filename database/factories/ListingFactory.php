<?php

namespace Database\Factories;

use App\Enums\ListingKind;
use App\Enums\ListingStatus;
use App\Models\Company;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->verified(),
            'user_id' => fn (array $attributes) => User::factory()->state(['company_id' => $attributes['company_id']]),
            'kind' => fake()->randomElement(ListingKind::cases()),
            'needed_by' => null,
            'destination' => null,
            'cut_point' => null,
            'notes' => null,
            'status' => ListingStatus::Open,
            'expires_at' => now()->addDays(2),
        ];
    }

    public function want(): static
    {
        return $this->state(fn () => ['kind' => ListingKind::Want]);
    }

    public function have(): static
    {
        return $this->state(fn () => ['kind' => ListingKind::Have]);
    }
}
