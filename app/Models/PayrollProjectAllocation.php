<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\PayrollRunStatus;
use Database\Factories\PayrollProjectAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'payroll_entry_id', 'company_id', 'project_id', 'project_site_id',
    'cost_center_id', 'expense_account_id', 'amount',
])]
class PayrollProjectAllocation extends Model
{
    /** @use HasFactory<PayrollProjectAllocationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $allocation): void {
            $entry = PayrollEntry::query()->with('payrollRun')->find($allocation->payroll_entry_id);
            if ($entry === null || (int) $entry->company_id !== (int) $allocation->company_id
                || ! in_array($entry->payrollRun->status, [PayrollRunStatus::Draft, PayrollRunStatus::Rejected], true)) {
                throw ValidationException::withMessages(['payroll_entry_id' => 'Allocations may only change on an editable same-company payroll entry.']);
            }
            if (bccomp((string) $allocation->amount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'Allocation amount must be positive.']);
            }
            $accountIsValid = Account::query()->whereKey($allocation->expense_account_id)
                ->where('company_id', $allocation->company_id)->where('account_type', AccountType::Expense)
                ->where('is_active', true)->where('allows_manual_posting', true)->exists();
            $projectIsValid = Project::query()->whereKey($allocation->project_id)
                ->where('company_id', $allocation->company_id)->exists();
            if (! $accountIsValid || ! $projectIsValid) {
                throw ValidationException::withMessages(['project_id' => 'Project and expense account must be active same-company posting dimensions.']);
            }
            if ($allocation->project_site_id !== null
                && ! ProjectSite::query()->whereKey($allocation->project_site_id)
                    ->where('company_id', $allocation->company_id)
                    ->where('project_id', $allocation->project_id)->exists()) {
                throw ValidationException::withMessages(['project_site_id' => 'Project site must belong to the selected company project.']);
            }
            if ($allocation->cost_center_id !== null
                && ! CostCenter::query()->whereKey($allocation->cost_center_id)
                    ->where('company_id', $allocation->company_id)->exists()) {
                throw ValidationException::withMessages(['cost_center_id' => 'Cost center must belong to the payroll company.']);
            }
        });

        static::deleting(function (self $allocation): void {
            $status = $allocation->payrollEntry()->firstOrFail()->payrollRun()->value('status');
            if (! in_array($status, [PayrollRunStatus::Draft->value, PayrollRunStatus::Rejected->value], true)) {
                throw ValidationException::withMessages(['payroll_entry_id' => 'Submitted payroll allocations are immutable.']);
            }
        });
    }

    public function payrollEntry(): BelongsTo
    {
        return $this->belongsTo(PayrollEntry::class);
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

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:4'];
    }
}
