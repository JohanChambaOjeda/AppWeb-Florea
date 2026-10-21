<?php

namespace App\Models;

use App\Enums\CutPoint;
use App\Enums\Destination;
use App\Enums\ListingKind;
use App\Enums\ListingStatus;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property ListingKind $kind
 * @property Carbon|null $needed_by
 * @property Destination|null $destination
 * @property CutPoint|null $cut_point
 * @property string|null $notes
 * @property ListingStatus $status
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string|null $first_offer_at agregado por withMin
 * @property-read int|null $offers_count agregado por withCount
 * @property-read int|null $new_offers_count agregado por withCount
 * @property-read int|null $accepted_offers_count agregado por withCount
 * @property-read Company $company
 * @property-read Collection<int, ListingItem> $items
 * @property-read Collection<int, Offer> $offers
 */
#[Fillable([
    'company_id', 'user_id', 'kind', 'needed_by', 'destination', 'cut_point',
    'notes', 'status', 'expires_at',
])]
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => ListingKind::class,
            'status' => ListingStatus::class,
            'destination' => Destination::class,
            'cut_point' => CutPoint::class,
            'needed_by' => 'date',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', ListingStatus::Open)->where('expires_at', '>', now());
    }

    /**
     * Abiertas cuyo plazo ya pasó. El comando listings:expire las cierra.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('status', ListingStatus::Open)->where('expires_at', '<=', now());
    }

    /**
     * Abierta y dentro de plazo: solo así se puede ofertar.
     */
    public function isOpen(): bool
    {
        return $this->status === ListingStatus::Open && $this->expires_at->isFuture();
    }

    public function belongsToCompany(?Company $company): bool
    {
        return $company !== null && $this->company_id === $company->id;
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
     * @return HasMany<ListingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ListingItem::class);
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * @return MorphMany<Photo, $this>
     */
    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable');
    }
}
