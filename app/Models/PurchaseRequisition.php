<?php

namespace App\Models;

use App\Enums\PurchaseRequisitionStatus;
use Database\Factories\PurchaseRequisitionFactory;
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
    'company_id', 'project_id', 'project_site_id', 'requisition_number', 'required_date',
    'status', 'approval_round', 'currency_code', 'reason', 'estimated_total', 'budget_check_status',
    'budget_check_snapshot', 'prepared_by_id', 'submitted_by_id', 'submitted_at',
    'approved_by_id', 'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason',
    'cancelled_by_id', 'cancelled_at', 'cancellation_reason',
])]
class PurchaseRequisition extends Model
{
    /** @use HasFactory<PurchaseRequisitionFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = [
        'status' => PurchaseRequisitionStatus::Draft->value,
        'approval_round' => 0,
        'currency_code' => 'PKR',
        'estimated_total' => 0,
        'budget_check_status' => 'not_checked',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $requisition): void {
            $projectMatches = Project::query()->whereKey($requisition->project_id)
                ->where('company_id', $requisition->company_id)->exists();
            $siteMatches = ProjectSite::query()->whereKey($requisition->project_site_id)
                ->where('company_id', $requisition->company_id)
                ->where('project_id', $requisition->project_id)->exists();

            if (! $projectMatches || ! $siteMatches) {
                throw ValidationException::withMessages(['project_site_id' => 'The project and site must belong to the requisition company.']);
            }

            if (! $requisition->exists) {
                return;
            }

            $persistedStatus = self::query()->whereKey($requisition)->value('status');
            if (in_array($persistedStatus, [
                PurchaseRequisitionStatus::Draft->value,
                PurchaseRequisitionStatus::Rejected->value,
            ], true)) {
                return;
            }

            $workflowFields = [
                'status', 'approval_round', 'requisition_number', 'estimated_total', 'budget_check_status',
                'budget_check_snapshot', 'submitted_by_id', 'submitted_at', 'approved_by_id',
                'approved_at', 'rejected_by_id', 'rejected_at', 'rejection_reason',
                'cancelled_by_id', 'cancelled_at', 'cancellation_reason', 'updated_at',
            ];

            if (array_diff(array_keys($requisition->getDirty()), $workflowFields) !== []) {
                throw ValidationException::withMessages(['status' => 'Submitted requisitions are immutable outside controlled workflow actions.']);
            }
        });

        static::deleting(function (self $requisition): void {
            if ($requisition->status !== PurchaseRequisitionStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only never-submitted draft requisitions may be deleted.']);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
        return $this->hasMany(PurchaseRequisitionLine::class)->orderBy('line_number');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function approvalSteps(): MorphMany
    {
        return $this->morphMany(ProcurementApprovalStep::class, 'approvable')->orderBy('step_number');
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

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [PurchaseRequisitionStatus::Draft, PurchaseRequisitionStatus::Rejected], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('purchase_requisitions')->logOnly([
            'company_id', 'project_id', 'project_site_id', 'requisition_number', 'required_date',
            'status', 'currency_code', 'reason', 'estimated_total', 'budget_check_status',
            'prepared_by_id', 'submitted_by_id', 'approved_by_id', 'rejected_by_id',
            'rejection_reason', 'cancelled_by_id', 'cancellation_reason',
        ])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'required_date' => 'date',
            'status' => PurchaseRequisitionStatus::class,
            'approval_round' => 'integer',
            'estimated_total' => 'decimal:4',
            'budget_check_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
