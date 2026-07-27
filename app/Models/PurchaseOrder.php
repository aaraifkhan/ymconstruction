<?php

namespace App\Models;

use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use Database\Factories\PurchaseOrderFactory;
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
    'company_id', 'purchase_requisition_id', 'vendor_id', 'project_id', 'project_site_id',
    'purchase_order_number', 'order_date', 'status', 'approval_round', 'currency_code', 'payment_terms_days',
    'payment_terms', 'notes', 'subtotal', 'tax_total', 'grand_total', 'approved_snapshot',
    'approved_snapshot_hash', 'prepared_by_id', 'submitted_by_id', 'submitted_at',
    'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason',
    'ordered_by_id', 'ordered_at', 'cancelled_by_id', 'cancelled_at', 'cancellation_reason',
])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => PurchaseOrderStatus::Draft->value,
        'approval_round' => 0,
        'currency_code' => 'PKR',
        'payment_terms_days' => 0,
        'subtotal' => 0,
        'tax_total' => 0,
        'grand_total' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $order): void {
            $projectMatches = Project::query()->whereKey($order->project_id)->where('company_id', $order->company_id)->exists();
            $siteMatches = ProjectSite::query()->whereKey($order->project_site_id)
                ->where('company_id', $order->company_id)->where('project_id', $order->project_id)->exists();
            $vendor = Party::query()->whereKey($order->vendor_id)->where('company_id', $order->company_id)->first();

            if (! $projectMatches || ! $siteMatches || $vendor === null || ! $vendor->hasRole(PartyRole::Vendor)) {
                throw ValidationException::withMessages(['vendor_id' => 'Vendor, project, and site must belong to the purchase-order company.']);
            }

            if ($order->purchase_requisition_id !== null
                && ! PurchaseRequisition::query()->whereKey($order->purchase_requisition_id)
                    ->where('company_id', $order->company_id)->where('project_id', $order->project_id)
                    ->where('project_site_id', $order->project_site_id)->exists()) {
                throw ValidationException::withMessages(['purchase_requisition_id' => 'The source requisition must match the purchase-order company, project, and site.']);
            }

            if (! $order->exists) {
                return;
            }

            $persistedStatus = self::query()->whereKey($order)->value('status');
            if (in_array($persistedStatus, [PurchaseOrderStatus::Draft->value, PurchaseOrderStatus::Rejected->value], true)) {
                return;
            }

            $workflowFields = [
                'status', 'approval_round', 'purchase_order_number', 'subtotal', 'tax_total', 'grand_total',
                'approved_snapshot', 'approved_snapshot_hash', 'submitted_by_id', 'submitted_at',
                'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at',
                'rejection_reason', 'ordered_by_id', 'ordered_at', 'cancelled_by_id',
                'cancelled_at', 'cancellation_reason', 'updated_at',
            ];

            if (array_diff(array_keys($order->getDirty()), $workflowFields) !== []) {
                throw ValidationException::withMessages(['status' => 'Submitted purchase orders are immutable outside controlled workflow actions.']);
            }

            if ($order->getOriginal('approved_snapshot_hash') !== null
                && ($order->isDirty('approved_snapshot') || $order->isDirty('approved_snapshot_hash'))) {
                throw ValidationException::withMessages(['approved_snapshot' => 'The approved purchase-order snapshot is immutable.']);
            }
        });

        static::deleting(function (self $order): void {
            if ($order->status !== PurchaseOrderStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only never-submitted draft purchase orders may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
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
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('line_number');
    }

    public function approvalSteps(): MorphMany
    {
        return $this->morphMany(ProcurementApprovalStep::class, 'approvable')->orderBy('step_number');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Rejected], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('purchase_orders')->logOnly([
            'company_id', 'purchase_requisition_id', 'vendor_id', 'project_id', 'project_site_id',
            'purchase_order_number', 'order_date', 'status', 'currency_code',
            'payment_terms_days', 'payment_terms', 'notes', 'subtotal', 'tax_total',
            'grand_total', 'approved_snapshot_hash', 'prepared_by_id', 'submitted_by_id',
            'approved_by_id', 'rejected_by_id', 'rejection_reason', 'ordered_by_id',
            'cancelled_by_id', 'cancellation_reason',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'status' => PurchaseOrderStatus::class,
            'approval_round' => 'integer',
            'payment_terms_days' => 'integer',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'approved_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'ordered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
