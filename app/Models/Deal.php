<?php

namespace App\Models;

use App\Enums\DeliveryResult;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $offer_id
 * @property int $buyer_company_id
 * @property int $seller_company_id
 * @property Carbon $closed_at
 * @property DeliveryResult|null $delivered
 * @property string|null $rejected_pct
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Offer $offer
 * @property-read Company $buyerCompany
 * @property-read Company $sellerCompany
 */
#[Fillable([
    'offer_id', 'buyer_company_id', 'seller_company_id', 'closed_at', 'delivered', 'rejected_pct',
])]
class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
            'delivered' => DeliveryResult::class,
            'rejected_pct' => 'decimal:2',
        ];
    }

    /**
     * ¿Existe un trato entre estas dos empresas (en cualquier sentido)?
     * Regla de negocio: solo entonces se muestra el teléfono de la otra.
     */
    public static function existsBetween(Company|int $a, Company|int $b): bool
    {
        $a = $a instanceof Company ? $a->id : $a;
        $b = $b instanceof Company ? $b->id : $b;

        return static::query()
            ->where(fn ($q) => $q->where('buyer_company_id', $a)->where('seller_company_id', $b))
            ->orWhere(fn ($q) => $q->where('buyer_company_id', $b)->where('seller_company_id', $a))
            ->exists();
    }

    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function buyerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'buyer_company_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function sellerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'seller_company_id');
    }

    /**
     * @return HasMany<Rating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
