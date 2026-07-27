<?php

namespace App\Models;

use App\Enums\InventoryTransactionStatus;
use App\Enums\InventoryTransactionType;
use Database\Factories\InventoryTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'transaction_number', 'type', 'status', 'transaction_date',
    'source_site_id', 'destination_site_id', 'project_id', 'goods_receipt_id',
    'reference', 'reason', 'prepared_by_id', 'posted_by_id', 'posted_at',
    'journal_entry_id', 'total_value',
])]
class InventoryTransaction extends Model
{
    /** @use HasFactory<InventoryTransactionFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => InventoryTransactionStatus::Draft->value,
        'total_value' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $transaction): void {
            $sourceMatches = $transaction->source_site_id === null || ProjectSite::query()
                ->whereKey($transaction->source_site_id)->where('company_id', $transaction->company_id)->exists();
            $destinationMatches = $transaction->destination_site_id === null || ProjectSite::query()
                ->whereKey($transaction->destination_site_id)->where('company_id', $transaction->company_id)->exists();
            $projectMatches = $transaction->project_id === null || Project::query()
                ->whereKey($transaction->project_id)->where('company_id', $transaction->company_id)->exists();
            $receiptMatches = $transaction->goods_receipt_id === null || GoodsReceipt::query()
                ->whereKey($transaction->goods_receipt_id)->where('company_id', $transaction->company_id)->exists();

            if (! $sourceMatches || ! $destinationMatches || ! $projectMatches || ! $receiptMatches) {
                throw ValidationException::withMessages(['company_id' => 'All inventory transaction dimensions must belong to one company.']);
            }

            $type = $transaction->type instanceof InventoryTransactionType
                ? $transaction->type
                : InventoryTransactionType::from($transaction->type);

            $validDimensions = match ($type) {
                InventoryTransactionType::Transfer => $transaction->source_site_id !== null
                    && $transaction->destination_site_id !== null
                    && (int) $transaction->source_site_id !== (int) $transaction->destination_site_id,
                InventoryTransactionType::ProjectIssue => $transaction->source_site_id !== null
                    && $transaction->project_id !== null,
                InventoryTransactionType::ProjectReturn => $transaction->destination_site_id !== null
                    && $transaction->project_id !== null,
                InventoryTransactionType::VendorReturn => $transaction->source_site_id !== null
                    && $transaction->goods_receipt_id !== null,
                InventoryTransactionType::AdjustmentIncrease => $transaction->destination_site_id !== null,
                InventoryTransactionType::AdjustmentDecrease => $transaction->source_site_id !== null,
            };

            if (! $validDimensions) {
                throw ValidationException::withMessages(['type' => 'The selected inventory transaction requires valid source, destination, Project, or Goods Receipt dimensions.']);
            }

            if ($transaction->exists
                && self::query()->whereKey($transaction)->value('status') === InventoryTransactionStatus::Posted->value) {
                throw ValidationException::withMessages(['status' => 'Posted inventory transactions are immutable.']);
            }
        });

        static::deleting(function (self $transaction): void {
            if ($transaction->status !== InventoryTransactionStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft inventory transactions may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'source_site_id');
    }

    public function destinationSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'destination_site_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryTransactionLine::class)->orderBy('line_number');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isEditable(): bool
    {
        return $this->status === InventoryTransactionStatus::Draft;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('inventory_transactions')->logOnly([
            'company_id', 'transaction_number', 'type', 'status', 'transaction_date',
            'source_site_id', 'destination_site_id', 'project_id', 'goods_receipt_id',
            'reference', 'reason', 'prepared_by_id', 'posted_by_id', 'journal_entry_id',
            'total_value',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'type' => InventoryTransactionType::class,
            'status' => InventoryTransactionStatus::class,
            'transaction_date' => 'date',
            'posted_at' => 'datetime',
            'total_value' => 'decimal:4',
        ];
    }
}
