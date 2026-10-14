<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public const ZONES = ['Tabacundo', 'La Esperanza', 'Tupigachi', 'Malchinguí', 'Tocachi'];

    public function run(): void
    {
        foreach (self::ZONES as $name) {
            Zone::firstOrCreate(['name' => $name], ['canton' => 'Pedro Moncayo']);
        }
    }
}
