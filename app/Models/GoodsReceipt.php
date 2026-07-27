<?php

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use Database\Factories\GoodsReceiptFactory;
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
    'company_id', 'purchase_order_id', 'vendor_id', 'project_id', 'project_site_id',
    'goods_receipt_number', 'delivery_reference', 'delivery_date', 'status',
    'receiving_notes', 'received_by_id', 'received_at', 'inspected_by_id',
    'inspected_at', 'inspection_notes', 'handed_over_by_id', 'handed_over_at',
    'inventory_journal_entry_id', 'accepted_value',
])]
class GoodsReceipt extends Model
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => GoodsReceiptStatus::Draft->value,
        'accepted_value' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $receipt): void {
            $order = PurchaseOrder::query()
                ->whereKey($receipt->purchase_order_id)
                ->where('company_id', $receipt->company_id)
                ->first();

            if ($order === null
                || ! in_array($order->status, [
                    PurchaseOrderStatus::Ordered,
                    PurchaseOrderStatus::PartiallyReceived,
                    PurchaseOrderStatus::Received,
                ], true)
                || (int) $order->vendor_id !== (int) $receipt->vendor_id
                || (int) $order->project_id !== (int) $receipt->project_id
                || (int) $order->project_site_id !== (int) $receipt->project_site_id) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'The issued purchase order, vendor, project, and site must belong to the Goods Receipt company.',
                ]);
            }

            if (! $receipt->exists) {
                return;
            }

            $persistedStatus = self::query()->whereKey($receipt)->value('status');
            if ($persistedStatus === GoodsReceiptStatus::Draft->value) {
                return;
            }

            $workflowFields = [
                'status', 'goods_receipt_number', 'received_by_id', 'received_at',
                'inspected_by_id', 'inspected_at', 'inspection_notes', 'handed_over_by_id',
                'handed_over_at', 'inventory_journal_entry_id', 'accepted_value', 'updated_at',
            ];

            if (array_diff(array_keys($receipt->getDirty()), $workflowFields) !== []) {
                throw ValidationException::withMessages([
                    'status' => 'Received Goods Receipt details are immutable outside controlled workflow actions.',
                ]);
            }
        });

        static::deleting(function (self $receipt): void {
            if ($receipt->status !== GoodsReceiptStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only draft Goods Receipts may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'vendor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class)->orderBy('line_number');
    }

    public function inventoryJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'inventory_journal_entry_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by_id');
    }

    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by_id');
    }

    public function isEditable(): bool
    {
        return $this->status === GoodsReceiptStatus::Draft;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('goods_receipts')->logOnly([
            'company_id', 'purchase_order_id', 'vendor_id', 'project_id', 'project_site_id',
            'goods_receipt_number', 'delivery_reference', 'delivery_date', 'status',
            'receiving_notes', 'received_by_id', 'inspected_by_id', 'inspection_notes',
            'handed_over_by_id', 'inventory_journal_entry_id', 'accepted_value',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'status' => GoodsReceiptStatus::class,
            'received_at' => 'datetime',
            'inspected_at' => 'datetime',
            'handed_over_at' => 'datetime',
            'accepted_value' => 'decimal:4',
        ];
    }
}
