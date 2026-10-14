<?php

namespace Database\Seeders;

use App\Models\Variety;
use Illuminate\Database\Seeder;

class VarietySeeder extends Seeder
{
    public const VARIETIES = [
        'Be Sweet', 'Brighton', 'Candlelight', 'Deep Purple', 'Explorer', 'Fortuner',
        'Freedom', 'Geraldine', 'Hard Rock', 'Hermosa', 'Kahala', 'Mandala', 'Mondial',
        'Nena', 'Paloma', 'Pink Floyd', 'Pink Mondial', 'Playa Blanca', 'Quicksand',
        'Shimmer', 'Toffee', 'Topaz', 'Vendela', 'Voodoo', 'White Chocolate',
    ];

    public function run(): void
    {
        foreach (self::VARIETIES as $name) {
            Variety::firstOrCreate(['name' => $name]);
        }
    }
}
