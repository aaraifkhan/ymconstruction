<?php

namespace App\Models;

use App\Enums\AssetAccountingStatus;
use Database\Factories\DepreciationRunLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'depreciation_run_id', 'company_id', 'fixed_asset_id', 'expense_account_id',
    'accumulated_depreciation_account_id', 'project_id', 'project_site_id', 'cost_center_id',
    'opening_accumulated_depreciation', 'depreciation_amount', 'closing_accumulated_depreciation', 'closing_carrying_amount',
])]
class DepreciationRunLine extends Model
{
    /** @use HasFactory<DepreciationRunLineFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $run = DepreciationRun::query()->find($line->depreciation_run_id);
            $asset = FixedAsset::query()->find($line->fixed_asset_id);
            if ($run === null || $run->status !== AssetAccountingStatus::Draft
                || $asset === null || (int) $run->company_id !== (int) $line->company_id
                || (int) $asset->company_id !== (int) $line->company_id) {
                throw ValidationException::withMessages(['depreciation_run_id' => 'Schedule lines require a draft same-company run and asset.']);
            }
        });

        static::deleting(function (self $line): void {
            if ($line->depreciationRun()->first()?->status !== AssetAccountingStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Submitted depreciation schedule lines are immutable.']);
            }
        });
    }

    public function depreciationRun(): BelongsTo
    {
        return $this->belongsTo(DepreciationRun::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    protected function casts(): array
    {
        return ['opening_accumulated_depreciation' => 'decimal:4', 'depreciation_amount' => 'decimal:4', 'closing_accumulated_depreciation' => 'decimal:4', 'closing_carrying_amount' => 'decimal:4'];
    }
}
