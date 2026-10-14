<?php

use App\Enums\UserRole;
use App\Models\CompanyVariety;
use App\Models\Deal;
use App\Models\ListingItem;
use App\Models\OfferItem;
use App\Models\Photo;
use App\Models\Rating;
use App\Models\User;
use App\Models\Variety;
use App\Models\Zone;
use Database\Seeders\AdminSeeder;
use Database\Seeders\VarietySeeder;
use Database\Seeders\ZoneSeeder;

it('crea registros con todas las factories', function () {
    CompanyVariety::factory()->create();
    ListingItem::factory()->create();
    OfferItem::factory()->create();
    Photo::factory()->create();
    $rating = Rating::factory()->create();

    expect(Deal::existsBetween($rating->deal->seller_company_id, $rating->deal->buyer_company_id))->toBeTrue()
        ->and(Photo::first()->photoable_type)->toBe('offer');
});

it('siembra sectores, variedades y el admin', function () {
    config(['florea.admin_phone' => '0991112233', 'florea.admin_password' => 'secreto']);

    $this->seed([ZoneSeeder::class, VarietySeeder::class, AdminSeeder::class]);

    $admin = User::where('role', UserRole::Admin)->sole();

    expect(Zone::count())->toBe(5)
        ->and(Variety::count())->toBe(25)
        ->and($admin->phone)->toBe('+593991112233')
        ->and($admin->company_id)->toBeNull();
});
