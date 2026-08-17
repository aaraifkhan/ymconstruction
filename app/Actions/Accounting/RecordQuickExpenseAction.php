<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecordQuickExpenseAction
{
    public function __construct(
        private SubmitJournalEntryAction $submitJournal,
    ) {}

    public function handle(
        Company $company,
        User $actor,
        CarbonInterface $date,
        ExpenseCategory $category,
        ExpensePaymentMethod $paymentMethod,
        string $amount,
        string $description,
        ?int $projectId = null,
        ?int $partyId = null,
        ?int $companyBankAccountId = null,
        ?int $expenseOfCompanyId = null,
        ?string $reference = null
    ): JournalEntry {
        if (bccomp($amount, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        if ($paymentMethod === ExpensePaymentMethod::Bank && $companyBankAccountId === null) {
            throw ValidationException::withMessages(['company_bank_account_id' => 'Select a bank account for bank payments.']);
        }

        if ($category->isDirectProjectCost() && $projectId === null) {
            throw ValidationException::withMessages(['project_id' => 'Direct project costs require a project selection.']);
        }

        return DB::transaction(function () use (
            $company,
            $actor,
            $date,
            $category,
            $paymentMethod,
            $amount,
            $description,
            $projectId,
            $partyId,
            $companyBankAccountId,
            $expenseOfCompanyId,
            $reference
        ): JournalEntry {
            $period = FinancialPeriod::query()
                ->where('company_id', $company->getKey())
                ->where('status', FinancialPeriodStatus::Open)
                ->whereDate('starts_on', '<=', $date)
                ->whereDate('ends_on', '>=', $date)
                ->lockForUpdate()
                ->first();

            if ($period === null) {
                throw ValidationException::withMessages(['date' => 'An open financial period is required for the transaction date.']);
            }

            $debitAccount = $this->resolveDebitAccount($company, $category);
            $creditAccount = $this->resolveCreditAccount($company, $paymentMethod, $companyBankAccountId);

            $voucherType = match ($paymentMethod) {
                ExpensePaymentMethod::Cash,
                ExpensePaymentMethod::Bank,
                ExpensePaymentMethod::PettyCash => VoucherType::Payment,
                default => VoucherType::Journal,
            };

            $journal = JournalEntry::create([
                'company_id' => $company->getKey(),
                'financial_year_id' => $period->financial_year_id,
                'financial_period_id' => $period->getKey(),
                'voucher_type' => $voucherType,
                'idempotency_key' => (string) Str::uuid(),
                'status' => JournalStatus::Draft,
                'transaction_date' => $date,
                'reference' => $reference,
                'description' => "{$category->getLabel()}: {$description}",
                'narration' => $description,
                'currency_code' => 'PKR',
                'prepared_by_id' => $actor->getKey(),
            ]);

            // Line 1: Debit Expense / Asset
            JournalLine::create([
                'journal_entry_id' => $journal->getKey(),
                'company_id' => $company->getKey(),
                'line_number' => 1,
                'account_id' => $debitAccount->getKey(),
                'account_code_snapshot' => $debitAccount->code,
                'account_name_snapshot' => $debitAccount->name,
                'debit' => $amount,
                'credit' => '0.0000',
                'project_id' => $projectId,
                'party_id' => $partyId,
                'related_company_id' => $expenseOfCompanyId,
                'description' => $description,
            ]);

            // Line 2: Credit Payment Fund / Liability
            JournalLine::create([
                'journal_entry_id' => $journal->getKey(),
                'company_id' => $company->getKey(),
                'line_number' => 2,
                'account_id' => $creditAccount->getKey(),
                'account_code_snapshot' => $creditAccount->code,
                'account_name_snapshot' => $creditAccount->name,
                'company_bank_account_id' => $companyBankAccountId,
                'debit' => '0.0000',
                'credit' => $amount,
                'description' => match ($paymentMethod) {
                    ExpensePaymentMethod::Director => 'Paid by Director out of pocket',
                    ExpensePaymentMethod::StaffPayable => 'Paid by staff member (Reimbursement Payable)',
                    default => $description,
                },
            ]);

            $this->submitJournal->handle($journal, $actor);

            activity('quick_expenses')->causedBy($actor)->performedOn($journal)->event('created')
                ->withProperties([
                    'company_id' => $company->getKey(),
                    'category' => $category->value,
                    'payment_method' => $paymentMethod->value,
                    'amount' => $amount,
                    'debit_account' => $debitAccount->code,
                    'credit_account' => $creditAccount->code,
                    'project_id' => $projectId,
                ])
                ->log('recorded quick expense');

            return $journal->refresh();
        });
    }

    private function resolveDebitAccount(Company $company, ExpenseCategory $category): Account
    {
        $code = $category->defaultAccountCode();
        $account = $company->accounts()->where('code', $code)->where('is_active', true)->first();

        if ($account !== null && $account->allows_manual_posting) {
            return $account;
        }

        // Fallback for employee advance
        if ($category === ExpenseCategory::EmployeeAdvance) {
            $mapping = AccountingMapping::where('company_id', $company->getKey())
                ->where('system_key', AccountingMappingKey::EmployeeAdvances)
                ->where('is_active', true)
                ->first();
            if ($mapping && $mapping->account) {
                return $mapping->account;
            }
        }

        // Fallback to active leaf expense account in 5000 / 7000 group
        $fallback = $company->accounts()
            ->where('allows_manual_posting', true)
            ->where('is_active', true)
            ->where(function ($query) use ($category): void {
                if ($category->isDirectProjectCost()) {
                    $query->where('code', 'LIKE', '7%');
                } else {
                    $query->where('code', 'LIKE', '5%')->orWhere('code', 'LIKE', '6%');
                }
            })
            ->first();

        if ($fallback !== null) {
            return $fallback;
        }

        throw ValidationException::withMessages(['category' => "No active posting account found for category {$category->getLabel()} (code {$code})."]);
    }

    private function resolveCreditAccount(
        Company $company,
        ExpensePaymentMethod $paymentMethod,
        ?int $companyBankAccountId
    ): Account {
        return match ($paymentMethod) {
            ExpensePaymentMethod::Cash => $this->mappedAccount($company, AccountingMappingKey::DefaultCash, 'Default Cash'),
            ExpensePaymentMethod::PettyCash => $this->mappedAccount($company, AccountingMappingKey::SitePettyCash, 'Site Petty Cash'),
            ExpensePaymentMethod::Director => $this->mappedAccount($company, AccountingMappingKey::DirectorLoan, 'Director Loan / Due to Director'),
            ExpensePaymentMethod::StaffPayable => $this->mappedAccount($company, AccountingMappingKey::StaffReimbursementPayable, 'Staff Reimbursement Payable'),
            ExpensePaymentMethod::Bank => $this->bankMappedAccount($company, $companyBankAccountId),
        };
    }

    private function mappedAccount(Company $company, AccountingMappingKey $key, string $label): Account
    {
        $mapping = AccountingMapping::where('company_id', $company->getKey())
            ->where('system_key', $key)
            ->where('is_active', true)
            ->with('account')
            ->first();

        if ($mapping && $mapping->account && $mapping->account->allows_manual_posting) {
            return $mapping->account;
        }

        // Fallback to account by system key directly on account model
        $account = $company->accounts()->where('system_key', $key)->where('is_active', true)->first();
        if ($account !== null) {
            return $account;
        }

        throw ValidationException::withMessages(['payment_method' => "Active accounting mapping for {$label} is required."]);
    }

    private function bankMappedAccount(Company $company, ?int $bankAccountId): Account
    {
        $mapping = AccountingMapping::where('company_id', $company->getKey())
            ->where('company_bank_account_id', $bankAccountId)
            ->where('is_active', true)
            ->with('account')
            ->first();

        if ($mapping && $mapping->account) {
            return $mapping->account;
        }

        throw ValidationException::withMessages(['company_bank_account_id' => 'Selected bank account does not have an active GL account mapping.']);
    }
}
