<?php

namespace App\Models;

use App\Enums\PaidAsAgreed;
use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $deal_id
 * @property int $from_company_id
 * @property int $to_company_id
 * @property int $score
 * @property bool|null $on_time
 * @property PaidAsAgreed|null $paid_as_agreed
 * @property bool|null $honored_agreement
 * @property string|null $comment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Deal $deal
 * @property-read Company $fromCompany
 * @property-read Company $toCompany
 */
#[Fillable([
    'deal_id', 'from_company_id', 'to_company_id', 'score', 'on_time',
    'paid_as_agreed', 'honored_agreement', 'comment',
])]
class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'on_time' => 'boolean',
            'paid_as_agreed' => PaidAsAgreed::class,
            'honored_agreement' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Deal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function fromCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'from_company_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function toCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'to_company_id');
    }
}
