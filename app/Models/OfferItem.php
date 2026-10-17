<?php

namespace App\Models;

use App\Enums\Unit;
use Database\Factories\OfferItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $offer_id
 * @property int $listing_item_id
 * @property string $quantity
 * @property Unit $unit
 * @property int|null $length
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Offer $offer
 * @property-read ListingItem $listingItem
 */
#[Fillable(['offer_id', 'listing_item_id', 'quantity', 'unit', 'length'])]
class OfferItem extends Model
{
    /** @use HasFactory<OfferItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit' => Unit::class,
            'length' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return BelongsTo<ListingItem, $this>
     */
    public function listingItem(): BelongsTo
    {
        return $this->belongsTo(ListingItem::class);
    }
}
