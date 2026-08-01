<?php

namespace App\Actions\Payroll;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Enums\AccountingMappingKey;
use App\Enums\EmploymentCategory;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\PayrollAccountComponent;
use App\Enums\PayrollRunStatus;
use App\Enums\VoucherType;
use App\Models\AccountingMapping;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\PayrollAccountMapping;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostPayrollRunAction
{
    public function __construct(
        private PostJournalEntryAction $postJournalEntry,
        private RecordPayrollFinancingRecoveriesAction $recordFinancingRecoveries,
    ) {}

    public function handle(PayrollRun $payrollRun, User $actor): PayrollRun
    {
        Gate::forUser($actor)->authorize('post', $payrollRun);

        return DB::transaction(function () use ($actor, $payrollRun): PayrollRun {
            $run = PayrollRun::query()->with(['entries.projectAllocations'])
                ->whereKey($payrollRun)->lockForUpdate()->firstOrFail();
            if ($run->isPostedToAccounts()) {
                return $run;
            }
            if ($run->status !== PayrollRunStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Only an approved payroll run may be posted.']);
            }

            $journal = $this->journalFor($run, $actor);
            $this->recordFinancingRecoveries->handle($run, $journal, $actor);
            $run->update([
                'journal_entry_id' => $journal->getKey(),
                'reversal_journal_entry_id' => null,
                'posted_by_id' => $actor->getKey(),
                'posted_at' => now(),
                'reversed_by_id' => null,
                'reversed_at' => null,
            ]);
            activity('payroll_runs')->causedBy($actor)->performedOn($run)->event('posted')
                ->withProperties(['company_id' => $run->company_id, 'journal_entry_id' => $journal->getKey()])
                ->log('posted payroll run to accounts');

            return $run->refresh();
        }, attempts: 3);
    }

    private function journalFor(PayrollRun $run, User $actor): JournalEntry
    {
        $revision = JournalEntry::query()->where('company_id', $run->company_id)
            ->where('source_type', $run::class)->where('source_id', $run->getKey())
            ->where('status', JournalStatus::Reversed)->count() + 1;
        $idempotencyKey = "PayrollRun:{$run->getKey()}:posting:{$revision}";
        $existing = JournalEntry::query()->where('company_id', $run->company_id)
            ->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
        if ($existing !== null) {
            return $existing->status === JournalStatus::Posted
                ? $existing
                : $this->postJournalEntry->handle($existing, $actor);
        }

        $period = FinancialPeriod::query()->where('company_id', $run->company_id)
            ->where('status', FinancialPeriodStatus::Open)
            ->whereDate('starts_on', '<=', $run->period_end)
            ->whereDate('ends_on', '>=', $run->period_end)
            ->lockForUpdate()->first();
        if ($period === null) {
            throw ValidationException::withMessages(['period_end' => 'Payroll period end requires an open financial period.']);
        }

        $journal = JournalEntry::query()->create([
            'company_id' => $run->company_id,
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => VoucherType::Payroll,
            'idempotency_key' => $idempotencyKey,
            'status' => JournalStatus::Draft,
            'transaction_date' => $run->period_end,
            'reference' => $run->reference_number,
            'description' => "Payroll {$run->reference_number}",
            'currency_code' => $run->currency_code,
            'source_type' => $run::class,
            'source_id' => $run->getKey(),
            'prepared_by_id' => $run->created_by_id,
        ]);

        $lineNumber = 1;
        foreach ($run->entries as $entry) {
            $lineNumber = $this->expenseLines($journal, $entry, $lineNumber);
            $lineNumber = $this->creditLine($journal, $entry, $lineNumber, AccountingMappingKey::SalaryPayable, (string) $entry->net_salary);
            $lineNumber = $this->creditLine($journal, $entry, $lineNumber, AccountingMappingKey::EmployeeAdvances, (string) $entry->loan_advance_deduction);
            $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::OtherDeduction, (string) $entry->other_deduction, true);
        }

        $journal->update([
            'status' => JournalStatus::Approved,
            'submitted_by_id' => $run->submitted_by_id,
            'submitted_at' => $run->submitted_at,
            'approved_by_id' => $run->approved_by_id,
            'approved_at' => $run->approved_at,
        ]);

        return $this->postJournalEntry->handle($journal, $actor);
    }

    private function expenseLines(JournalEntry $journal, PayrollEntry $entry, int $lineNumber): int
    {
        if ($entry->employment_category === EmploymentCategory::ProjectStaff->value) {
            if (bccomp((string) $entry->projectAllocations->sum('amount'), $entry->expenseBasis(), 4) !== 0) {
                throw ValidationException::withMessages(['project_allocations' => "Project allocation is incomplete for {$entry->employee_name}."]);
            }
            foreach ($entry->projectAllocations as $allocation) {
                $journal->lines()->create([
                    'company_id' => $entry->company_id, 'line_number' => $lineNumber++,
                    'account_id' => $allocation->expense_account_id, 'description' => $entry->employee_name,
                    'debit' => $allocation->amount, 'credit' => '0.0000', 'employment_id' => $entry->employment_id,
                    'project_id' => $allocation->project_id, 'project_site_id' => $allocation->project_site_id,
                    'cost_center_id' => $allocation->cost_center_id,
                ]);
            }

            return $lineNumber;
        }

        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::BasicSalary, (string) $entry->payable_basic);
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::HouseTravelAllowance, (string) $entry->house_travel_allowance);
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::FuelAllowance, (string) ($entry->fuel_allowance ?? 0));
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::MobileAllowance, (string) ($entry->mobile_allowance ?? 0));
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::InternetAllowance, (string) ($entry->internet_allowance ?? 0));
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::FoodAllowance, (string) $entry->food_allowance);
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::SiteAllowance, (string) ($entry->site_allowance ?? 0));
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::ProjectAllowance, (string) ($entry->project_allowance ?? 0));
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::OtherAllowance, (string) $entry->other_allowance);
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::Bonus, (string) ($entry->bonus_amount ?? 0));
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::Incentive, (string) ($entry->incentive_amount ?? 0));
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::AbsenceDeduction, (string) $entry->absence_deduction, true);
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::UnpaidLeaveDeduction, (string) ($entry->unpaid_leave_deduction ?? 0), true);
        $lineNumber = $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::LateDeduction, (string) ($entry->late_deduction ?? 0), true);

        return $this->componentLine($journal, $entry, $lineNumber, PayrollAccountComponent::HalfDayDeduction, (string) ($entry->half_day_deduction ?? 0), true);
    }

    private function componentLine(
        JournalEntry $journal,
        PayrollEntry $entry,
        int $lineNumber,
        PayrollAccountComponent $component,
        string $amount,
        bool $credit = false,
    ): int {
        if (bccomp($amount, '0', 4) !== 1) {
            return $lineNumber;
        }
        $accountId = PayrollAccountMapping::query()->where('company_id', $entry->company_id)
            ->where('component', $component)->where('is_active', true)->value('account_id');
        if ($accountId === null) {
            throw ValidationException::withMessages(['payroll_account_mapping' => "Missing active {$component->label()} payroll mapping."]);
        }
        $journal->lines()->create([
            'company_id' => $entry->company_id, 'line_number' => $lineNumber,
            'account_id' => $accountId, 'description' => "{$entry->employee_name}: {$component->label()}",
            'debit' => $credit ? '0.0000' : $amount, 'credit' => $credit ? $amount : '0.0000',
            'employment_id' => $entry->employment_id,
        ]);

        return $lineNumber + 1;
    }

    private function creditLine(
        JournalEntry $journal,
        PayrollEntry $entry,
        int $lineNumber,
        AccountingMappingKey $mappingKey,
        string $amount,
    ): int {
        if (bccomp($amount, '0', 4) !== 1) {
            return $lineNumber;
        }
        $accountId = AccountingMapping::query()->where('company_id', $entry->company_id)
            ->where('system_key', $mappingKey)->where('is_active', true)->value('account_id');
        if ($accountId === null) {
            throw ValidationException::withMessages(['accounting_mapping' => "Missing active {$mappingKey->value} accounting mapping."]);
        }
        $journal->lines()->create([
            'company_id' => $entry->company_id, 'line_number' => $lineNumber,
            'account_id' => $accountId, 'description' => $entry->employee_name,
            'debit' => '0.0000', 'credit' => $amount, 'employment_id' => $entry->employment_id,
        ]);

        return $lineNumber + 1;
    }
}
