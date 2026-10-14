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

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_varieties')
            ->using(CompanyVariety::class)
            ->withPivot('lengths')
            ->withTimestamps();
    }

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
