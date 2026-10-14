<?php

namespace App\Models;

use Database\Factories\CompanyVarietyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }
}
