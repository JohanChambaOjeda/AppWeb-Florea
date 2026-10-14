<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $phone = PhoneNumber::normalize(config('florea.admin_phone'));
        $password = config('florea.admin_password');

        if ($phone === null || blank($password)) {
            throw new RuntimeException('Configura ADMIN_PHONE (celular de Ecuador) y ADMIN_PASSWORD en .env antes de sembrar el admin.');
        }

        User::updateOrCreate(
            ['phone' => $phone],
            [
                'name' => 'Johan Chamba',
                'company_id' => null,
                'role' => UserRole::Admin,
                'password' => $password,
            ],
        );
    }
}
