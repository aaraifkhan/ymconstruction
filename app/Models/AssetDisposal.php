<?php

namespace App\Models;

use App\Enums\AssetAccountingStatus;
use Database\Factories\AssetDisposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'company_id', 'fixed_asset_id', 'proceeds_account_id', 'disposal_date', 'proceeds_amount',
    'cost_amount', 'accumulated_depreciation_amount', 'carrying_amount', 'gain_amount', 'loss_amount',
    'status', 'reason', 'prepared_by_id', 'approved_by_id', 'approved_at', 'posted_by_id', 'posted_at',
    'journal_entry_id', 'reversal_journal_entry_id', 'reversed_by_id', 'reversed_at',
])]
class AssetDisposal extends Model
{
    /** @use HasFactory<AssetDisposalFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'draft', 'proceeds_amount' => 0, 'gain_amount' => 0, 'loss_amount' => 0];

    protected static function booted(): void
    {
        static::saving(function (self $disposal): void {
            if (bccomp((string) $disposal->proceeds_amount, '0', 4) === -1) {
                throw ValidationException::withMessages(['proceeds_amount' => 'Disposal proceeds cannot be negative.']);
            }
            if (! FixedAsset::query()->whereKey($disposal->fixed_asset_id)->where('company_id', $disposal->company_id)->exists()) {
                throw ValidationException::withMessages(['fixed_asset_id' => 'Choose a same-company fixed asset.']);
            }
            if ($disposal->proceeds_account_id !== null
                && ! Account::query()->whereKey($disposal->proceeds_account_id)->where('company_id', $disposal->company_id)
                    ->where('is_active', true)->where('allows_manual_posting', true)->exists()) {
                throw ValidationException::withMessages(['proceeds_account_id' => 'Choose an active same-company posting account.']);
            }
            if ($disposal->exists && self::query()->find($disposal->getKey())?->status !== AssetAccountingStatus::Draft) {
                $workflowFields = [
                    'status', 'posted_by_id', 'posted_at', 'journal_entry_id', 'reversal_journal_entry_id',
                    'reversed_by_id', 'reversed_at', 'updated_at',
                ];
                if (array_diff(array_keys($disposal->getDirty()), $workflowFields) !== []) {
                    throw ValidationException::withMessages(['status' => 'Approved disposal terms and snapshots are immutable.']);
                }
            }
        });

        static::deleting(function (self $disposal): void {
            if ($disposal->status !== AssetAccountingStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft disposals may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    protected function casts(): array
    {
        return ['status' => AssetAccountingStatus::class, 'disposal_date' => 'date', 'proceeds_amount' => 'decimal:4', 'cost_amount' => 'decimal:4', 'accumulated_depreciation_amount' => 'decimal:4', 'carrying_amount' => 'decimal:4', 'gain_amount' => 'decimal:4', 'loss_amount' => 'decimal:4', 'approved_at' => 'datetime', 'posted_at' => 'datetime', 'reversed_at' => 'datetime'];
    }
}
