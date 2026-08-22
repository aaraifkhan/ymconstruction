<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\ExpensePaymentMethod;
use App\Enums\FinancialPeriodStatus;
use App\Enums\IncomeCategory;
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

class RecordQuickIncomeAction
{
    public function __construct(
        private SubmitJournalEntryAction $submitJournal,
    ) {}

    public function handle(
        Company $company,
        User $actor,
        CarbonInterface $date,
        IncomeCategory $category,
        ExpensePaymentMethod $receivingMethod,
        string $amount,
        string $description,
        ?int $projectId = null,
        ?int $partyId = null,
        ?int $companyBankAccountId = null,
        ?string $reference = null
    ): JournalEntry {
        if (bccomp($amount, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        if ($receivingMethod === ExpensePaymentMethod::Bank && $companyBankAccountId === null) {
            throw ValidationException::withMessages(['company_bank_account_id' => 'Select a bank account for bank receipt.']);
        }

        return DB::transaction(function () use (
            $company,
            $actor,
            $date,
            $category,
            $receivingMethod,
            $amount,
            $description,
            $projectId,
            $partyId,
            $companyBankAccountId,
            $reference
        ): JournalEntry {
            $period = FinancialPeriod::withoutGlobalScopes()
                ->where('company_id', $company->getKey())
                ->where('status', FinancialPeriodStatus::Open)
                ->whereDate('starts_on', '<=', $date)
                ->whereDate('ends_on', '>=', $date)
                ->lockForUpdate()
                ->first();

            if ($period === null) {
                throw ValidationException::withMessages(['date' => 'An open financial period is required for the transaction date.']);
            }

            $debitAccount = $this->resolveDebitAccount($company, $receivingMethod, $companyBankAccountId);
            $creditAccount = $this->resolveCreditAccount($company, $category);

            $journal = JournalEntry::create([
                'company_id' => $company->getKey(),
                'financial_year_id' => $period->financial_year_id,
                'financial_period_id' => $period->getKey(),
                'voucher_type' => VoucherType::Receipt,
                'idempotency_key' => (string) Str::uuid(),
                'status' => JournalStatus::Draft,
                'transaction_date' => $date,
                'reference' => $reference,
                'description' => "{$category->getLabel()}: {$description}",
                'narration' => $description,
                'currency_code' => 'PKR',
                'prepared_by_id' => $actor->getKey(),
            ]);

            // Line 1: Debit Receiving Cash / Bank Account
            JournalLine::create([
                'journal_entry_id' => $journal->getKey(),
                'company_id' => $company->getKey(),
                'line_number' => 1,
                'account_id' => $debitAccount->getKey(),
                'account_code_snapshot' => $debitAccount->code,
                'account_name_snapshot' => $debitAccount->name,
                'company_bank_account_id' => $companyBankAccountId,
                'debit' => $amount,
                'credit' => '0.0000',
                'description' => $description,
            ]);

            // Line 2: Credit Income / Capital / AR Account
            JournalLine::create([
                'journal_entry_id' => $journal->getKey(),
                'company_id' => $company->getKey(),
                'line_number' => 2,
                'account_id' => $creditAccount->getKey(),
                'account_code_snapshot' => $creditAccount->code,
                'account_name_snapshot' => $creditAccount->name,
                'debit' => '0.0000',
                'credit' => $amount,
                'project_id' => $projectId,
                'party_id' => $partyId,
                'description' => $description,
            ]);

            $this->submitJournal->handle($journal, $actor);

            activity('quick_income')->causedBy($actor)->performedOn($journal)->event('created')
                ->withProperties([
                    'company_id' => $company->getKey(),
                    'category' => $category->value,
                    'receiving_method' => $receivingMethod->value,
                    'amount' => $amount,
                    'debit_account' => $debitAccount->code,
                    'credit_account' => $creditAccount->code,
                    'project_id' => $projectId,
                    'party_id' => $partyId,
                ])
                ->log('recorded quick income / receipt');

            return $journal->refresh();
        });
    }

    private function resolveDebitAccount(
        Company $company,
        ExpensePaymentMethod $receivingMethod,
        ?int $companyBankAccountId
    ): Account {
        return match ($receivingMethod) {
            ExpensePaymentMethod::Cash => $this->mappedAccount($company, AccountingMappingKey::DefaultCash, 'Default Cash'),
            ExpensePaymentMethod::PettyCash => $this->mappedAccount($company, AccountingMappingKey::SitePettyCash, 'Site Petty Cash'),
            ExpensePaymentMethod::Bank => $this->bankMappedAccount($company, $companyBankAccountId),
            default => $this->mappedAccount($company, AccountingMappingKey::DefaultCash, 'Default Cash'),
        };
    }

    private function resolveCreditAccount(Company $company, IncomeCategory $category): Account
    {
        $code = $category->defaultAccountCode();
        $account = $company->accounts()
            ->withoutGlobalScopes()
            ->where('code', $code)
            ->where('is_active', true)
            ->where('allows_manual_posting', true)
            ->whereDoesntHave('children')
            ->first();

        if ($account !== null) {
            return $account;
        }

        // Mappings check
        $mappingKey = match ($category) {
            IncomeCategory::CustomerReceipt => AccountingMappingKey::AccountsReceivable,
            IncomeCategory::CustomerAdvance => AccountingMappingKey::CustomerAdvances,
            IncomeCategory::DirectorLoan => AccountingMappingKey::DirectorLoan,
            default => null,
        };

        if ($mappingKey !== null) {
            $mapping = AccountingMapping::withoutGlobalScopes()
                ->where('company_id', $company->getKey())
                ->where('system_key', $mappingKey)
                ->where('is_active', true)
                ->first();
            if ($mapping && $mapping->account && $mapping->account->allows_manual_posting && ! $mapping->account->children()->exists()) {
                return $mapping->account;
            }
        }

        // Fallback to active leaf posting account
        $fallback = $company->accounts()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->where('allows_manual_posting', true)
            ->whereDoesntHave('children')
            ->where(function ($query) use ($category): void {
                if (in_array($category, [IncomeCategory::ConstructionRevenue, IncomeCategory::ServiceRevenue, IncomeCategory::ConsultancyIncome, IncomeCategory::RentalIncome, IncomeCategory::OtherIncome], true)) {
                    $query->where('code', 'LIKE', '4%');
                } elseif ($category === IncomeCategory::DirectorCapital) {
                    $query->where('code', 'LIKE', '3%');
                } else {
                    $query->where('code', 'LIKE', '4%')->orWhere('code', 'LIKE', '2%');
                }
            })
            ->first();

        if ($fallback !== null) {
            return $fallback;
        }

        throw ValidationException::withMessages(['category' => "No active posting account found for income category {$category->getLabel()} (code {$code})."]);
    }

    private function mappedAccount(Company $company, AccountingMappingKey $key, string $label): Account
    {
        $mapping = AccountingMapping::withoutGlobalScopes()
            ->where('company_id', $company->getKey())
            ->where('system_key', $key)
            ->where('is_active', true)
            ->with('account')
            ->first();

        if ($mapping && $mapping->account && $mapping->account->allows_manual_posting) {
            return $mapping->account;
        }

        $account = $company->accounts()->withoutGlobalScopes()->where('system_key', $key)->where('is_active', true)->first();
        if ($account !== null) {
            return $account;
        }

        throw ValidationException::withMessages(['receiving_method' => "Active accounting mapping for {$label} is required."]);
    }

    private function bankMappedAccount(Company $company, ?int $bankAccountId): Account
    {
        $mapping = AccountingMapping::withoutGlobalScopes()
            ->where('company_id', $company->getKey())
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
