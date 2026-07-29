<?php

namespace App\Actions\HR;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Enums\AccountingMappingKey;
use App\Enums\EmployeeFinancingTransactionType;
use App\Enums\FinalSettlementComponentNature;
use App\Enums\FinalSettlementStatus;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingMapping;
use App\Models\EmployeeFinancing;
use App\Models\FinalSettlement;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostFinalSettlementAction
{
    public function __construct(
        private PostJournalEntryAction $postJournal,
        private ApplyEmployeeFinancingRecoveryAction $applyFinancingRecovery,
    ) {}

    public function handle(FinalSettlement $settlement, User $actor): FinalSettlement
    {
        Gate::forUser($actor)->authorize('post', $settlement);

        return DB::transaction(function () use ($settlement, $actor): FinalSettlement {
            $settlement = FinalSettlement::query()->with('lines')->whereKey($settlement)
                ->lockForUpdate()->firstOrFail();
            if (in_array($settlement->status, [
                FinalSettlementStatus::Posted, FinalSettlementStatus::Settled,
            ], true)) {
                return $settlement;
            }
            if ($settlement->status !== FinalSettlementStatus::Approved
                || in_array((int) $actor->getKey(), [
                    (int) $settlement->prepared_by_id, (int) $settlement->reviewed_by_id,
                    (int) $settlement->approved_by_id,
                ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Posting requires an approved settlement and an independent Finance poster.',
                ]);
            }
            if (bccomp((string) $settlement->net_amount, '0', 4) === 0) {
                $settlement->update([
                    'status' => FinalSettlementStatus::Settled,
                    'posted_by_id' => $actor->getKey(),
                    'posted_at' => now(),
                ]);
                activity('final_settlements')->causedBy($actor)->performedOn($settlement)->event('settled')
                    ->withProperties([
                        'company_id' => $settlement->company_id,
                        'balance_direction' => $settlement->balance_direction,
                        'zero_balance' => true,
                    ])->log('settled zero-balance final settlement');

                return $settlement->refresh();
            }

            $journal = $this->journalFor($settlement, $actor);
            foreach ($settlement->lines->whereNotNull('employee_financing_id') as $line) {
                $financing = EmployeeFinancing::query()->whereKey($line->employee_financing_id)
                    ->lockForUpdate()->firstOrFail();
                $this->applyFinancingRecovery->handle(
                    $financing,
                    (string) $line->amount,
                    EmployeeFinancingTransactionType::FinalSettlementRecovery,
                    "final-settlement:{$settlement->getKey()}:line:{$line->getKey()}",
                    CarbonImmutable::instance($settlement->cutoff_date),
                    $actor,
                    journalEntry: $journal,
                    reason: "Final Settlement {$settlement->reference_number}",
                    authorize: false,
                );
            }
            $settlement->update([
                'status' => FinalSettlementStatus::Posted,
                'posted_by_id' => $actor->getKey(),
                'posted_at' => now(),
                'journal_entry_id' => $journal->getKey(),
            ]);
            activity('final_settlements')->causedBy($actor)->performedOn($settlement)->event('posted')
                ->withProperties([
                    'company_id' => $settlement->company_id,
                    'journal_entry_id' => $journal->getKey(),
                    'balance_direction' => $settlement->balance_direction,
                ])->log('posted final settlement');

            return $settlement->refresh();
        }, 3);
    }

    private function journalFor(FinalSettlement $settlement, User $actor): JournalEntry
    {
        $idempotencyKey = "FinalSettlement:{$settlement->getKey()}:posting";
        $existing = JournalEntry::query()->where('company_id', $settlement->company_id)
            ->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
        if ($existing !== null) {
            return $existing->status === JournalStatus::Posted
                ? $existing
                : $this->postJournal->handle($existing, $actor);
        }
        $period = FinancialPeriod::query()->where('company_id', $settlement->company_id)
            ->where('status', FinancialPeriodStatus::Open)
            ->whereDate('starts_on', '<=', $settlement->cutoff_date)
            ->whereDate('ends_on', '>=', $settlement->cutoff_date)
            ->lockForUpdate()->first();
        if ($period === null) {
            throw ValidationException::withMessages(['cutoff_date' => 'Settlement cutoff requires an open financial period.']);
        }
        $journal = JournalEntry::query()->create([
            'company_id' => $settlement->company_id,
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => VoucherType::Payroll,
            'idempotency_key' => $idempotencyKey,
            'status' => JournalStatus::Draft,
            'transaction_date' => $settlement->cutoff_date,
            'reference' => $settlement->reference_number,
            'description' => "Final Settlement {$settlement->reference_number}",
            'currency_code' => $settlement->currency_code,
            'source_type' => $settlement::class,
            'source_id' => $settlement->getKey(),
            'prepared_by_id' => $settlement->prepared_by_id,
        ]);
        $lineNumber = 1;
        foreach ($settlement->lines as $line) {
            $isRecovery = $line->nature === FinalSettlementComponentNature::Recovery;
            $journal->lines()->create([
                'company_id' => $settlement->company_id,
                'line_number' => $lineNumber++,
                'account_id' => $line->account_id,
                'description' => $line->description,
                'debit' => $isRecovery ? '0.0000' : $line->amount,
                'credit' => $isRecovery ? $line->amount : '0.0000',
                'employment_id' => $settlement->employment_id,
            ]);
        }
        $balanceMapping = $settlement->balance_direction === 'payable'
            ? AccountingMappingKey::SalaryPayable : AccountingMappingKey::EmployeeAdvances;
        $accountId = AccountingMapping::query()->where('company_id', $settlement->company_id)
            ->where('system_key', $balanceMapping)->where('is_active', true)->value('account_id');
        if ($accountId === null) {
            throw ValidationException::withMessages(['account_mapping' => "Missing active {$balanceMapping->value} mapping."]);
        }
        $payable = $settlement->balance_direction === 'payable';
        $journal->lines()->create([
            'company_id' => $settlement->company_id,
            'line_number' => $lineNumber,
            'account_id' => $accountId,
            'description' => "{$settlement->reference_number} net {$settlement->balance_direction}",
            'debit' => $payable ? '0.0000' : $settlement->net_amount,
            'credit' => $payable ? $settlement->net_amount : '0.0000',
            'employment_id' => $settlement->employment_id,
        ]);
        $journal->update([
            'status' => JournalStatus::Approved,
            'submitted_by_id' => $settlement->submitted_by_id,
            'submitted_at' => $settlement->submitted_at,
            'approved_by_id' => $settlement->approved_by_id,
            'approved_at' => $settlement->approved_at,
        ]);

        return $this->postJournal->handle($journal, $actor);
    }
}
