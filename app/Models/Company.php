<?php

namespace App\Models;

use App\Enums\CompanyType;
use App\Enums\Plan;
use App\Enums\UserRole;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type', 'name', 'tax_id', 'zone_id', 'address_reference', 'id_document_path',
    'verified_at', 'verified_by', 'plan', 'plan_until',
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

    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->whereNotNull('verified_at');
    }

    #[Scope]
    protected function pendingVerification(Builder $query): void
    {
        $query->whereNull('verified_at');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isGrower(): bool
    {
        return $this->type === CompanyType::Grower;
    }

    public function isPostharvest(): bool
    {
        return $this->type === CompanyType::Postharvest;
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', UserRole::Owner)->oldestOfMany();
    }

    public function varieties(): BelongsToMany
    {
        return $this->belongsToMany(Variety::class, 'company_varieties')
            ->using(CompanyVariety::class)
            ->withPivot('lengths')
            ->withTimestamps();
    }

    public function companyVarieties(): HasMany
    {
        return $this->hasMany(CompanyVariety::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function dealsAsBuyer(): HasMany
    {
        return $this->hasMany(Deal::class, 'buyer_company_id');
    }

    public function dealsAsSeller(): HasMany
    {
        return $this->hasMany(Deal::class, 'seller_company_id');
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'to_company_id');
    }
}
