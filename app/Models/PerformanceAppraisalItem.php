<?php

namespace App\Models;

use App\Enums\PerformanceAppraisalStatus;
use Database\Factories\PerformanceAppraisalItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'performance_appraisal_id', 'performance_kpi_id',
    'goal', 'weight', 'score', 'reviewer_comments',
])]
#[Hidden(['goal', 'score', 'reviewer_comments'])]
class PerformanceAppraisalItem extends Model
{
    /** @use HasFactory<PerformanceAppraisalItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $appraisal = PerformanceAppraisal::query()->whereKey($item->performance_appraisal_id)
                ->where('company_id', $item->company_id)->with('cycle')->first();
            $kpiMatches = PerformanceKpi::query()->whereKey($item->performance_kpi_id)
                ->where('company_id', $item->company_id)->exists();
            if ($appraisal === null || ! $kpiMatches
                || ! in_array($appraisal->status, [PerformanceAppraisalStatus::Draft, PerformanceAppraisalStatus::Rejected], true)
                || bccomp((string) $item->weight, '0', 4) !== 1
                || ($item->score !== null && (
                    bccomp((string) $item->score, (string) $appraisal->cycle->score_min, 4) === -1
                    || bccomp((string) $item->score, (string) $appraisal->cycle->score_max, 4) === 1
                ))) {
                throw ValidationException::withMessages([
                    'performance_kpi_id' => 'Use a same-company KPI, positive weight, and score within the cycle scale on an editable appraisal.',
                ]);
            }
        });
        static::deleting(function (self $item): void {
            if (! in_array($item->appraisal()->value('status'), [
                PerformanceAppraisalStatus::Draft->value,
                PerformanceAppraisalStatus::Rejected->value,
            ], true)) {
                throw ValidationException::withMessages(['status' => 'Submitted appraisal items are immutable.']);
            }
        });
    }

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(PerformanceAppraisal::class, 'performance_appraisal_id');
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpi::class, 'performance_kpi_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
            'goal' => 'encrypted',
            'score' => 'encrypted',
            'reviewer_comments' => 'encrypted',
        ];
    }
}
