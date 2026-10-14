<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'photoable_type' => 'offer',
            'photoable_id' => Offer::factory(),
            'path' => 'photos/'.fake()->uuid().'.jpg',
            'taken_at' => now(),
        ];
    }
}
