<?php

namespace App\Actions\HR;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Enums\AccountingMappingKey;
use App\Enums\AccountType;
use App\Enums\EmployeeFinancingTransactionType;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\EmployeeFinancing;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WaiveEmployeeFinancingAction
{
    public function __construct(
        private ApplyEmployeeFinancingRecoveryAction $applyRecovery,
        private PostJournalEntryAction $postJournalEntry,
    ) {}

    public function handle(
        EmployeeFinancing $financing,
        string $amount,
        Account $expenseAccount,
        CarbonImmutable $effectiveDate,
        string $reason,
        User $actor,
    ): EmployeeFinancing {
        Gate::forUser($actor)->authorize('waive', $financing);

        return DB::transaction(function () use ($financing, $amount, $expenseAccount, $effectiveDate, $reason, $actor): EmployeeFinancing {
            $financing = EmployeeFinancing::query()->whereKey($financing)->lockForUpdate()->firstOrFail();
            if ((int) $expenseAccount->company_id !== (int) $financing->company_id
                || $expenseAccount->account_type !== AccountType::Expense
                || ! $expenseAccount->is_active
                || $expenseAccount->children()->exists()
                || trim($reason) === '') {
                throw ValidationException::withMessages(['expense_account_id' => 'Waiver requires a same-company active posting Expense account and reason.']);
            }
            if (bccomp((string) $financing->finance_charge, '0', 4) !== 0) {
                throw ValidationException::withMessages(['finance_charge' => 'Finance-charge waiver requires a separately approved interest-income accounting design.']);
            }
            $idempotencyKey = 'EmployeeFinancing:'.$financing->getKey().':waiver:'.str()->uuid();
            $period = FinancialPeriod::query()->where('company_id', $financing->company_id)
                ->where('status', FinancialPeriodStatus::Open)
                ->whereDate('starts_on', '<=', $effectiveDate)
                ->whereDate('ends_on', '>=', $effectiveDate)
                ->lockForUpdate()->first();
            if ($period === null) {
                throw ValidationException::withMessages(['effective_date' => 'Waiver requires an open financial period.']);
            }
            $receivableAccountId = AccountingMapping::query()
                ->where('company_id', $financing->company_id)
                ->where('system_key', AccountingMappingKey::EmployeeAdvances)
                ->where('is_active', true)->value('account_id');
            if ($receivableAccountId === null) {
                throw ValidationException::withMessages(['accounting_mapping' => 'Employee Advances accounting mapping is required.']);
            }
            $journal = JournalEntry::query()->create([
                'company_id' => $financing->company_id,
                'financial_year_id' => $period->financial_year_id,
                'financial_period_id' => $period->getKey(),
                'voucher_type' => VoucherType::Journal,
                'idempotency_key' => $idempotencyKey,
                'status' => JournalStatus::Draft,
                'transaction_date' => $effectiveDate,
                'reference' => $financing->reference_number,
                'description' => "Employee financing waiver: {$reason}",
                'currency_code' => 'PKR',
                'source_type' => $financing::class,
                'source_id' => $financing->getKey(),
                'prepared_by_id' => $financing->requested_by_id,
                'submitted_by_id' => $financing->submitted_by_id,
                'submitted_at' => $financing->submitted_at,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);
            $journal->lines()->create([
                'company_id' => $financing->company_id,
                'line_number' => 1,
                'account_id' => $expenseAccount->getKey(),
                'description' => $reason,
                'debit' => $amount,
                'credit' => '0.0000',
                'employment_id' => $financing->employment_id,
            ]);
            $journal->lines()->create([
                'company_id' => $financing->company_id,
                'line_number' => 2,
                'account_id' => $receivableAccountId,
                'description' => $reason,
                'debit' => '0.0000',
                'credit' => $amount,
                'employment_id' => $financing->employment_id,
            ]);
            $journal->update(['status' => JournalStatus::Approved]);
            $journal = $this->postJournalEntry->handle($journal, $actor);
            $financing = $this->applyRecovery->handle(
                $financing,
                $amount,
                EmployeeFinancingTransactionType::Waiver,
                $idempotencyKey,
                $effectiveDate,
                $actor,
                journalEntry: $journal,
                reason: $reason,
            );

            return $financing->refresh();
        }, 3);
    }
}
