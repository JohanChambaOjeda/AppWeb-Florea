<?php

namespace App\Models;

use Database\Factories\CompanyVarietyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $variety_id
 * @property list<int> $lengths
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Company $company
 * @property-read Variety $variety
 */
#[Fillable(['company_id', 'variety_id', 'lengths'])]
class CompanyVariety extends Pivot
{
    /** @use HasFactory<CompanyVarietyFactory> */
    use HasFactory;

    protected $table = 'company_varieties';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'lengths' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Variety, $this>
     */
    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }
}
