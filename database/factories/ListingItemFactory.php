<?php

namespace Database\Factories;

use App\Enums\Unit;
use App\Models\Listing;
use App\Models\ListingItem;
use App\Models\Variety;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingItem>
 */
class ListingItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'variety_id' => Variety::factory(),
            'lengths' => [fake()->randomElement(config('florea.standard_lengths'))],
            'quantity' => fake()->numberBetween(1, 15),
            'unit' => Unit::Hb,
        ];
    }

    public function withoutQuantity(): static
    {
        return $this->state(fn () => ['quantity' => null, 'unit' => null]);
    }
}
