<?php

namespace App\Models;

use App\Enums\Delivery;
use App\Enums\OfferStatus;
use App\Enums\PaymentTerm;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $listing_id
 * @property int $company_id
 * @property int $user_id
 * @property string|null $price_per_stem
 * @property Delivery $delivery
 * @property string|null $notes
 * @property OfferStatus $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Listing $listing
 * @property-read Company $company
 * @property-read User $user
 * @property-read Deal|null $deal
 * @property-read Collection<int, OfferItem> $items
 * @property-read Collection<int, Photo> $photos
 */
#[Fillable([
    'listing_id', 'company_id', 'user_id', 'price_per_stem', 'delivery', 'payment_term',
    'notes', 'status',
])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_per_stem' => 'decimal:2',
            'delivery' => Delivery::class,
            'payment_term' => PaymentTerm::class,
            'status' => OfferStatus::class,
        ];
    }

    /**
     * Enviada y esperando respuesta: se puede editar o retirar.
     */
    public function isSent(): bool
    {
        return $this->status === OfferStatus::Sent;
    }

    /**
     * Cuenta como oferta viva en la publicación.
     */
    public function isLive(): bool
    {
        return in_array($this->status, [OfferStatus::Sent, OfferStatus::Accepted], true);
    }

    /**
     * Se puede volver a enviar sobre esta misma fila: o está enviada (la
     * edito) o la retiré (vuelvo a ofertar). Una rechazada no: no se insiste.
     */
    public function canBeReplaced(): bool
    {
        return in_array($this->status, [OfferStatus::Sent, OfferStatus::Withdrawn], true);
    }

    public function belongsToCompany(?Company $company): bool
    {
        return $company !== null && $this->company_id === $company->id;
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OfferItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OfferItem::class);
    }

    /**
     * @return MorphMany<Photo, $this>
     */
    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable');
    }

    /**
     * @return HasOne<Deal, $this>
     */
    public function deal(): HasOne
    {
        return $this->hasOne(Deal::class);
    }
}
