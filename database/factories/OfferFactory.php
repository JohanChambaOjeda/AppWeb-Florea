<?php

namespace Database\Factories;

use App\Enums\Delivery;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'company_id' => Company::factory()->grower()->verified(),
            'user_id' => fn (array $attributes) => User::factory()->state(['company_id' => $attributes['company_id']]),
            'price_per_stem' => fake()->randomFloat(2, 0.15, 0.60),
            'delivery' => fake()->randomElement(Delivery::cases()),
            'notes' => null,
            'status' => OfferStatus::Sent,
        ];
    }
}
