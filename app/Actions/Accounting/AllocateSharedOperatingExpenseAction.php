<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
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

class AllocateSharedOperatingExpenseAction
{
    public function __construct(
        private SubmitJournalEntryAction $submitJournal,
    ) {}

    /**
     * @param  array<int, array{company_id: int, amount: string}>  $shares
     * @return array{paying_journal: JournalEntry, recipient_journals: array<int, JournalEntry>}
     */
    public function handle(
        Company $payingCompany,
        User $actor,
        CarbonInterface $date,
        ExpenseCategory $category,
        ExpensePaymentMethod $paymentMethod,
        string $totalAmount,
        string $description,
        array $shares,
        ?int $companyBankAccountId = null
    ): array {
        if (bccomp($totalAmount, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages(['total_amount' => 'Total amount must be greater than zero.']);
        }

        if (empty($shares)) {
            throw ValidationException::withMessages(['shares' => 'At least one company share allocation is required.']);
        }

        // Verify share sum matches total
        $shareSum = '0.0000';
        foreach ($shares as $share) {
            if (bccomp($share['amount'], '0.0000', 4) <= 0) {
                throw ValidationException::withMessages(['shares' => 'Each allocated share amount must be positive.']);
            }
            $shareSum = bcadd($shareSum, $share['amount'], 4);
        }

        if (bccomp($shareSum, $totalAmount, 4) !== 0) {
            throw ValidationException::withMessages([
                'shares' => "Allocated shares sum (PKR {$shareSum}) does not match total amount (PKR {$totalAmount}).",
            ]);
        }

        return DB::transaction(function () use (
            $payingCompany,
            $actor,
            $date,
            $category,
            $paymentMethod,
            $totalAmount,
            $description,
            $shares,
            $companyBankAccountId
        ): array {
            // 1. Validate paying company period
            $payingPeriod = FinancialPeriod::query()
                ->where('company_id', $payingCompany->getKey())
                ->where('status', FinancialPeriodStatus::Open)
                ->whereDate('starts_on', '<=', $date)
                ->whereDate('ends_on', '>=', $date)
                ->lockForUpdate()
                ->first();

            if ($payingPeriod === null) {
                throw ValidationException::withMessages(['date' => "Open financial period required for paying company {$payingCompany->name}."]);
            }

            // Paying company expense account
            $payingExpenseAccount = $payingCompany->accounts()->where('code', $category->defaultAccountCode())->where('is_active', true)->firstOrFail();

            // Credit account on paying company (Cash, Bank, Director, Petty Cash)
            $payingCreditAccount = match ($paymentMethod) {
                ExpensePaymentMethod::Cash => AccountingMapping::where('company_id', $payingCompany->getKey())->where('system_key', AccountingMappingKey::DefaultCash)->where('is_active', true)->firstOrFail()->account,
                ExpensePaymentMethod::PettyCash => AccountingMapping::where('company_id', $payingCompany->getKey())->where('system_key', AccountingMappingKey::SitePettyCash)->where('is_active', true)->firstOrFail()->account,
                ExpensePaymentMethod::Director => AccountingMapping::where('company_id', $payingCompany->getKey())->where('system_key', AccountingMappingKey::DirectorLoan)->where('is_active', true)->firstOrFail()->account,
                ExpensePaymentMethod::Bank => AccountingMapping::where('company_id', $payingCompany->getKey())->where('company_bank_account_id', $companyBankAccountId)->where('is_active', true)->firstOrFail()->account,
                ExpensePaymentMethod::StaffPayable => AccountingMapping::where('company_id', $payingCompany->getKey())->where('system_key', AccountingMappingKey::StaffReimbursementPayable)->where('is_active', true)->firstOrFail()->account,
            };

            // Paying company Due From Related Companies account
            $dueFromMapping = AccountingMapping::where('company_id', $payingCompany->getKey())
                ->where('system_key', AccountingMappingKey::DueFromRelatedCompanies)
                ->where('is_active', true)
                ->firstOrFail();
            $dueFromAccount = $dueFromMapping->account;

            // Create paying company Journal Entry
            $payingJournal = JournalEntry::create([
                'company_id' => $payingCompany->getKey(),
                'financial_year_id' => $payingPeriod->financial_year_id,
                'financial_period_id' => $payingPeriod->getKey(),
                'source_type' => Company::class,
                'source_id' => $payingCompany->getKey(),
                'voucher_type' => match ($paymentMethod) {
                    ExpensePaymentMethod::Cash, ExpensePaymentMethod::PettyCash, ExpensePaymentMethod::Bank => VoucherType::Payment,
                    default => VoucherType::Journal,
                },
                'idempotency_key' => (string) Str::uuid(),
                'status' => JournalStatus::Draft,
                'transaction_date' => $date,
                'description' => "Shared {$category->getLabel()} allocation: {$description}",
                'narration' => $description,
                'currency_code' => 'PKR',
                'prepared_by_id' => $actor->getKey(),
            ]);

            $lineNumber = 1;
            $recipientJournals = [];

            foreach ($shares as $share) {
                $comp = Company::findOrFail($share['company_id']);
                $shareAmount = $share['amount'];

                if ((int) $comp->getKey() === (int) $payingCompany->getKey()) {
                    // Paying company's own share: Debit own expense account
                    JournalLine::create([
                        'journal_entry_id' => $payingJournal->getKey(),
                        'company_id' => $payingCompany->getKey(),
                        'line_number' => $lineNumber++,
                        'account_id' => $payingExpenseAccount->getKey(),
                        'account_code_snapshot' => $payingExpenseAccount->code,
                        'account_name_snapshot' => $payingExpenseAccount->name,
                        'debit' => $shareAmount,
                        'credit' => '0.0000',
                        'description' => "Own share of shared {$category->getLabel()} - {$description}",
                    ]);
                } else {
                    // Other entity's share: Debit Due from Related Companies (with related_company_id)
                    JournalLine::create([
                        'journal_entry_id' => $payingJournal->getKey(),
                        'company_id' => $payingCompany->getKey(),
                        'line_number' => $lineNumber++,
                        'account_id' => $dueFromAccount->getKey(),
                        'account_code_snapshot' => $dueFromAccount->code,
                        'account_name_snapshot' => $dueFromAccount->name,
                        'related_company_id' => $comp->getKey(),
                        'debit' => $shareAmount,
                        'credit' => '0.0000',
                        'description' => "Shared {$category->getLabel()} claim from {$comp->name} - {$description}",
                    ]);

                    // Create paired Journal on recipient company:
                    // Debit Recipient Expense / Credit Due to Related Companies (Paying Co)
                    $recipientPeriod = FinancialPeriod::query()
                        ->where('company_id', $comp->getKey())
                        ->where('status', FinancialPeriodStatus::Open)
                        ->whereDate('starts_on', '<=', $date)
                        ->whereDate('ends_on', '>=', $date)
                        ->lockForUpdate()
                        ->first();

                    if ($recipientPeriod !== null) {
                        $recipientExpenseAccount = $comp->accounts()->where('code', $category->defaultAccountCode())->where('is_active', true)->first()
                            ?? $comp->accounts()->where('code', 'LIKE', '5%')->where('allows_manual_posting', true)->first();

                        $dueToMapping = AccountingMapping::where('company_id', $comp->getKey())
                            ->where('system_key', AccountingMappingKey::DueToRelatedCompanies)
                            ->where('is_active', true)
                            ->first();

                        if ($recipientExpenseAccount !== null && $dueToMapping !== null && $dueToMapping->account !== null) {
                            $recJournal = JournalEntry::create([
                                'company_id' => $comp->getKey(),
                                'financial_year_id' => $recipientPeriod->financial_year_id,
                                'financial_period_id' => $recipientPeriod->getKey(),
                                'source_type' => Company::class,
                                'source_id' => $comp->getKey(),
                                'voucher_type' => VoucherType::Journal,
                                'idempotency_key' => (string) Str::uuid(),
                                'status' => JournalStatus::Draft,
                                'transaction_date' => $date,
                                'description' => "Shared {$category->getLabel()} allocated from {$payingCompany->name}: {$description}",
                                'narration' => $description,
                                'currency_code' => 'PKR',
                                'prepared_by_id' => $actor->getKey(),
                            ]);

                            JournalLine::create([
                                'journal_entry_id' => $recJournal->getKey(),
                                'company_id' => $comp->getKey(),
                                'line_number' => 1,
                                'account_id' => $recipientExpenseAccount->getKey(),
                                'account_code_snapshot' => $recipientExpenseAccount->code,
                                'account_name_snapshot' => $recipientExpenseAccount->name,
                                'debit' => $shareAmount,
                                'credit' => '0.0000',
                                'description' => "Shared {$category->getLabel()} from {$payingCompany->name}",
                            ]);

                            JournalLine::create([
                                'journal_entry_id' => $recJournal->getKey(),
                                'company_id' => $comp->getKey(),
                                'line_number' => 2,
                                'account_id' => $dueToMapping->account->getKey(),
                                'account_code_snapshot' => $dueToMapping->account->code,
                                'account_name_snapshot' => $dueToMapping->account->name,
                                'related_company_id' => $payingCompany->getKey(),
                                'debit' => '0.0000',
                                'credit' => $shareAmount,
                                'description' => "Payable to {$payingCompany->name} for shared {$category->getLabel()}",
                            ]);

                            $this->submitJournal->handle($recJournal, $actor);
                            $recipientJournals[$comp->getKey()] = $recJournal->refresh();
                        }
                    }
                }
            }

            // Final credit line on paying company for full amount
            JournalLine::create([
                'journal_entry_id' => $payingJournal->getKey(),
                'company_id' => $payingCompany->getKey(),
                'line_number' => $lineNumber,
                'account_id' => $payingCreditAccount->getKey(),
                'account_code_snapshot' => $payingCreditAccount->code,
                'account_name_snapshot' => $payingCreditAccount->name,
                'company_bank_account_id' => $companyBankAccountId,
                'debit' => '0.0000',
                'credit' => $totalAmount,
                'description' => "Total payment for shared {$category->getLabel()} - {$description}",
            ]);

            $this->submitJournal->handle($payingJournal, $actor);

            activity('shared_allocation')->causedBy($actor)->performedOn($payingJournal)->event('allocated')
                ->withProperties([
                    'paying_company_id' => $payingCompany->getKey(),
                    'total_amount' => $totalAmount,
                    'category' => $category->value,
                    'shares' => $shares,
                ])
                ->log('recorded shared operating expense allocation');

            return [
                'paying_journal' => $payingJournal->refresh(),
                'recipient_journals' => $recipientJournals,
            ];
        });
    }
}
