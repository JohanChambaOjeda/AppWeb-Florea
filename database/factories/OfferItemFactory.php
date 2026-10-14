<?php

namespace Database\Factories;

use App\Enums\Unit;
use App\Models\ListingItem;
use App\Models\Offer;
use App\Models\OfferItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfferItem>
 */
class OfferItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'listing_item_id' => ListingItem::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'unit' => Unit::Hb,
            'length' => fake()->randomElement(config('florea.standard_lengths')),
        ];
    }
}
