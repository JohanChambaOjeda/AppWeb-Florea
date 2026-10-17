<?php

namespace App\Models;

use Database\Factories\VarietyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CompanyVariety|null $pivot
 * @property-read bool|null $used_by_companies agregado por withExists
 * @property-read bool|null $used_in_listings  agregado por withExists
 */
#[Fillable(['name', 'color', 'is_active'])]
class Variety extends Model
{
    /** @use HasFactory<VarietyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return BelongsToMany<Company, $this, CompanyVariety>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_varieties')
            ->using(CompanyVariety::class)
            ->withPivot('lengths')
            ->withTimestamps();
    }

    /**
     * @return HasMany<ListingItem, $this>
     */
    public function listingItems(): HasMany
    {
        return $this->hasMany(ListingItem::class);
    }

    /**
     * Una variedad en uso (por productores o publicaciones) no se puede borrar.
     */
    public function isInUse(): bool
    {
        return $this->companies()->exists() || $this->listingItems()->exists();
    }
}
