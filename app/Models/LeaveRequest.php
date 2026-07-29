<?php

namespace App\Models;

use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveRequestStatus;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'company_id', 'employment_id', 'leave_type_id', 'leave_policy_id',
    'starts_on', 'ends_on', 'requested_units', 'reason', 'status',
    'is_paid_snapshot', 'payroll_impact_snapshot', 'requested_by_id',
    'requested_at', 'manager_decided_by_id', 'manager_decided_at',
    'hr_decided_by_id', 'hr_decided_at', 'decision_reason',
    'cancelled_by_id', 'cancelled_at',
])]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $attributes = ['status' => 'draft'];

    protected static function booted(): void
    {
        static::saving(function (LeaveRequest $request): void {
            if ($request->exists && in_array(
                $request->getRawOriginal('status'),
                [LeaveRequestStatus::Approved->value, LeaveRequestStatus::Rejected->value, LeaveRequestStatus::Cancelled->value],
                true,
            )) {
                throw ValidationException::withMessages(['status' => 'Decided leave requests are immutable outside controlled workflows.']);
            }

            if ($request->ends_on->lt($request->starts_on)) {
                throw ValidationException::withMessages(['ends_on' => 'The end date must be on or after the start date.']);
            }

            if ((float) $request->requested_units <= 0) {
                throw ValidationException::withMessages(['requested_units' => 'Requested units must be greater than zero.']);
            }

            $employment = null;
            foreach (['employment_id' => Employment::class, 'leave_type_id' => LeaveType::class] as $field => $model) {
                $record = $model::query()->whereKey($request->{$field})
                    ->where('company_id', $request->company_id)->first();
                if ($record === null) {
                    throw ValidationException::withMessages([$field => 'The selected record must belong to the same company.']);
                }
                if ($record instanceof Employment) {
                    $employment = $record;
                }
            }

            if ($employment === null
                || $request->starts_on->lt($employment->joining_date)
                || ($employment->ending_date !== null && $request->ends_on->gt($employment->ending_date))) {
                throw ValidationException::withMessages([
                    'starts_on' => 'Leave must fall within the Employment lifecycle.',
                ]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function managerDecidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_decided_by_id');
    }

    public function hrDecidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_decided_by_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('leave_requests')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'requested_units' => 'decimal:2',
            'status' => LeaveRequestStatus::class,
            'is_paid_snapshot' => 'boolean',
            'payroll_impact_snapshot' => LeavePayrollImpact::class,
            'requested_at' => 'datetime',
            'manager_decided_at' => 'datetime',
            'hr_decided_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
