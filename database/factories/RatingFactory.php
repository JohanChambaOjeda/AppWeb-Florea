<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'deal_id' => Deal::factory(),
            'from_company_id' => fn (array $attributes) => Deal::find($attributes['deal_id'])->buyer_company_id,
            'to_company_id' => fn (array $attributes) => Deal::find($attributes['deal_id'])->seller_company_id,
            'score' => fake()->numberBetween(1, 5),
            'on_time' => fake()->boolean(),
            'comment' => null,
        ];
    }
}
