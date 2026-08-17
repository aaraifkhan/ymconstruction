<?php

namespace App\Actions\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransferBiddingCostToProjectAction
{
    public function __construct(
        private SubmitJournalEntryAction $submitJournal,
    ) {}

    public function handle(
        Company $company,
        User $actor,
        Project $project,
        CarbonInterface $date,
        string $amount,
        string $description = 'Transfer of pre-award tender/bidding costs to won project',
        string $sourceBiddingAccountCode = '5050',
        string $targetProjectCostAccountCode = '7290'
    ): JournalEntry {
        if (bccomp($amount, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        if ((int) $project->company_id !== (int) $company->getKey()) {
            throw ValidationException::withMessages(['project_id' => 'Selected project does not belong to this company.']);
        }

        return DB::transaction(function () use (
            $company,
            $actor,
            $project,
            $date,
            $amount,
            $description,
            $sourceBiddingAccountCode,
            $targetProjectCostAccountCode
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

            // Target Account: 7000 series direct cost
            $targetAccount = $company->accounts()->where('code', $targetProjectCostAccountCode)->where('is_active', true)->first()
                ?? $company->accounts()->where('code', 'LIKE', '7%')->where('allows_manual_posting', true)->firstOrFail();

            // Source Account: 5050 series bidding cost
            $sourceAccount = $company->accounts()->where('code', $sourceBiddingAccountCode)->where('is_active', true)->first()
                ?? $company->accounts()->where('code', 'LIKE', '505%')->where('allows_manual_posting', true)->firstOrFail();

            $journal = JournalEntry::create([
                'company_id' => $company->getKey(),
                'financial_year_id' => $period->financial_year_id,
                'financial_period_id' => $period->getKey(),
                'voucher_type' => VoucherType::Journal,
                'idempotency_key' => (string) Str::uuid(),
                'status' => JournalStatus::Draft,
                'transaction_date' => $date,
                'description' => "Capitalize Bidding Cost to Project {$project->name}: {$description}",
                'narration' => $description,
                'currency_code' => 'PKR',
                'prepared_by_id' => $actor->getKey(),
            ]);

            // Line 1: Debit Project Direct Cost (with project dimension)
            JournalLine::create([
                'journal_entry_id' => $journal->getKey(),
                'company_id' => $company->getKey(),
                'line_number' => 1,
                'account_id' => $targetAccount->getKey(),
                'account_code_snapshot' => $targetAccount->code,
                'account_name_snapshot' => $targetAccount->name,
                'project_id' => $project->getKey(),
                'debit' => $amount,
                'credit' => '0.0000',
                'description' => "Pre-award bidding costs transferred to project {$project->name}",
            ]);

            // Line 2: Credit Bidding Expense (clearing the pre-award expense)
            JournalLine::create([
                'journal_entry_id' => $journal->getKey(),
                'company_id' => $company->getKey(),
                'line_number' => 2,
                'account_id' => $sourceAccount->getKey(),
                'account_code_snapshot' => $sourceAccount->code,
                'account_name_snapshot' => $sourceAccount->name,
                'debit' => '0.0000',
                'credit' => $amount,
                'description' => "Bidding expense capitalized to project {$project->name}",
            ]);

            $this->submitJournal->handle($journal, $actor);

            activity('bidding_transfer')->causedBy($actor)->performedOn($journal)->event('transfer')
                ->withProperties([
                    'company_id' => $company->getKey(),
                    'project_id' => $project->getKey(),
                    'amount' => $amount,
                    'journal_entry_id' => $journal->getKey(),
                ])
                ->log("transferred bidding costs to project {$project->name}");

            return $journal->refresh();
        });
    }
}
