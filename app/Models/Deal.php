<?php

namespace App\Models;

use App\Enums\DeliveryResult;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function buyerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'buyer_company_id');
    }

    public function sellerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'seller_company_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
