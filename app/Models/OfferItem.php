<?php

namespace App\Models;

use App\Enums\Unit;
use Database\Factories\OfferItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function listingItem(): BelongsTo
    {
        return $this->belongsTo(ListingItem::class);
    }
}
