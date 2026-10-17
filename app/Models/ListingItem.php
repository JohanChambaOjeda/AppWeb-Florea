<?php

namespace App\Models;

use App\Enums\Unit;
use Database\Factories\ListingItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $listing_id
 * @property int $variety_id
 * @property list<int> $lengths
 * @property string|null $quantity
 * @property Unit|null $unit
 * @property-read Listing $listing
 * @property-read Variety $variety
 */
#[Fillable(['listing_id', 'variety_id', 'lengths', 'quantity', 'unit'])]
class ListingItem extends Model
{
    /** @use HasFactory<ListingItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'lengths' => 'array',
            'quantity' => 'decimal:2',
            'unit' => Unit::class,
        ];
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<Variety, $this>
     */
    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    /**
     * @return HasMany<OfferItem, $this>
     */
    public function offerItems(): HasMany
    {
        return $this->hasMany(OfferItem::class);
    }
}
