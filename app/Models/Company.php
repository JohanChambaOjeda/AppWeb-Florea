<?php

namespace App\Models;

use App\Enums\CompanyType;
use App\Enums\Plan;
use App\Enums\UserRole;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property CompanyType $type
 * @property string $name
 * @property string $tax_id
 * @property int $zone_id
 * @property string|null $address_reference
 * @property string|null $id_document_path
 * @property Carbon|null $verified_at
 * @property int|null $verified_by
 * @property string|null $rejection_reason
 * @property Plan $plan
 * @property Carbon|null $plan_until
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Zone $zone
 * @property-read User|null $owner
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Variety> $varieties
 * @property-read Collection<int, CompanyVariety> $companyVarieties
 */
#[Fillable([
    'type', 'name', 'tax_id', 'zone_id', 'address_reference', 'id_document_path',
    'verified_at', 'verified_by', 'rejection_reason', 'plan', 'plan_until',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => CompanyType::class,
            'plan' => Plan::class,
            'verified_at' => 'datetime',
            'plan_until' => 'date',
        ];
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->whereNotNull('verified_at');
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function pendingVerification(Builder $query): void
    {
        $query->whereNull('verified_at');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isRejected(): bool
    {
        return $this->trashed();
    }

    public function verify(User $admin): void
    {
        $this->forceFill([
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'rejection_reason' => null,
        ])->save();
    }

    /**
     * Rechazar = soft delete con motivo opcional. Sus usuarios ven el motivo al ingresar.
     */
    public function reject(?string $reason = null): void
    {
        $this->forceFill(['rejection_reason' => $reason])->save();
        $this->delete();
    }

    public function isGrower(): bool
    {
        return $this->type === CompanyType::Grower;
    }

    public function isPostharvest(): bool
    {
        return $this->type === CompanyType::Postharvest;
    }

    /**
     * @return BelongsTo<Zone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasOne<User, $this>
     */
    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', UserRole::Owner)->oldestOfMany();
    }

    /**
     * @return BelongsToMany<Variety, $this, CompanyVariety>
     */
    public function varieties(): BelongsToMany
    {
        return $this->belongsToMany(Variety::class, 'company_varieties')
            ->using(CompanyVariety::class)
            ->withPivot('lengths')
            ->withTimestamps();
    }

    /**
     * @return HasMany<CompanyVariety, $this>
     */
    public function companyVarieties(): HasMany
    {
        return $this->hasMany(CompanyVariety::class);
    }

    /**
     * @return HasMany<Listing, $this>
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * @return HasMany<Deal, $this>
     */
    public function dealsAsBuyer(): HasMany
    {
        return $this->hasMany(Deal::class, 'buyer_company_id');
    }

    /**
     * @return HasMany<Deal, $this>
     */
    public function dealsAsSeller(): HasMany
    {
        return $this->hasMany(Deal::class, 'seller_company_id');
    }

    /**
     * @return HasMany<Rating, $this>
     */
    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'to_company_id');
    }
}
