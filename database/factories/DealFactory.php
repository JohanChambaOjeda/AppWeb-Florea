<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            // Quien compra y quien vende salen de la oferta: en un "Busco" el
            // que oferta vende, así que la publicación pone al comprador.
            'buyer_company_id' => fn (array $attributes) => Offer::findOrFail((int) $attributes['offer_id'])->listing->company_id,
            'seller_company_id' => fn (array $attributes) => Offer::findOrFail((int) $attributes['offer_id'])->company_id,
            'closed_at' => now(),
            'delivered' => null,
            'rejected_pct' => null,
        ];
    }
}
