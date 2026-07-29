<?php

namespace App\Actions\HR;

use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Enums\EmployeeFinancingInstallmentStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingTransactionType;
use App\Enums\FinalSettlementStatus;
use App\Enums\TreasuryStatus;
use App\Models\EmployeeFinancing;
use App\Models\EmployeeFinancingInstallment;
use App\Models\EmployeeFinancingTransaction;
use App\Models\FinalSettlement;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseFinalSettlementAction
{
    public function __construct(private ReverseJournalEntryAction $reverseJournal) {}

    public function handle(
        FinalSettlement $settlement,
        User $actor,
        CarbonInterface $reversalDate,
        string $reason,
    ): FinalSettlement {
        Gate::forUser($actor)->authorize('reverse', $settlement);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($settlement, $actor, $reversalDate, $reason): FinalSettlement {
            $settlement = FinalSettlement::query()->whereKey($settlement)->lockForUpdate()->firstOrFail();
            if ($settlement->status === FinalSettlementStatus::Reversed) {
                return $settlement;
            }
            if ($settlement->status === FinalSettlementStatus::Settled
                && $settlement->journal_entry_id === null
                && bccomp((string) $settlement->net_amount, '0', 4) === 0) {
                $settlement->update([
                    'status' => FinalSettlementStatus::Reversed,
                    'reversed_by_id' => $actor->getKey(),
                    'reversed_at' => now(),
                ]);
                activity('final_settlements')->causedBy($actor)->performedOn($settlement)->event('reversed')
                    ->withProperties([
                        'company_id' => $settlement->company_id,
                        'reason' => $reason,
                        'zero_balance' => true,
                    ])->log('reversed zero-balance final settlement');

                return $settlement->refresh();
            }
            if (! in_array($settlement->status, [
                FinalSettlementStatus::Posted, FinalSettlementStatus::Settled,
            ], true) || $settlement->journal_entry_id === null) {
                throw ValidationException::withMessages(['status' => 'Only a posted Final Settlement may be reversed.']);
            }
            if ($settlement->treasuryAllocations()
                ->whereHas('treasuryTransaction', fn ($query) => $query->whereIn('status', [
                    TreasuryStatus::Draft, TreasuryStatus::Submitted,
                    TreasuryStatus::Approved, TreasuryStatus::Posted,
                ]))->exists()) {
                throw ValidationException::withMessages([
                    'treasury' => 'Remove draft allocations, reject submitted/approved transactions, or reverse posted settlement transactions first.',
                ]);
            }
            $reversal = $this->reverseJournal->handle(
                $settlement->journalEntry()->firstOrFail(),
                $actor,
                $reversalDate,
                $reason,
            );
            $transactions = EmployeeFinancingTransaction::query()
                ->where('journal_entry_id', $settlement->journal_entry_id)
                ->where('type', EmployeeFinancingTransactionType::FinalSettlementRecovery)
                ->whereNotIn('id', EmployeeFinancingTransaction::query()
                    ->whereNotNull('reversal_of_id')->select('reversal_of_id'))
                ->lockForUpdate()->get();
            foreach ($transactions as $transaction) {
                $installment = EmployeeFinancingInstallment::query()
                    ->whereKey($transaction->employee_financing_installment_id)->lockForUpdate()->firstOrFail();
                $principal = bcsub((string) $installment->principal_recovered, (string) $transaction->principal_amount, 4);
                $charge = bcsub((string) $installment->finance_charge_recovered, (string) $transaction->finance_charge_amount, 4);
                EmployeeFinancingInstallment::query()->whereKey($installment)->update([
                    'principal_recovered' => $principal,
                    'finance_charge_recovered' => $charge,
                    'status' => bccomp(bcadd($principal, $charge, 4), '0', 4) === 1
                        ? EmployeeFinancingInstallmentStatus::Partial->value
                        : EmployeeFinancingInstallmentStatus::Pending->value,
                    'updated_at' => now(),
                ]);
                $financing = EmployeeFinancing::query()->whereKey($transaction->employee_financing_id)
                    ->lockForUpdate()->firstOrFail();
                $financing->transactions()->create([
                    'company_id' => $financing->company_id,
                    'employee_financing_installment_id' => $installment->getKey(),
                    'journal_entry_id' => $reversal->getKey(),
                    'reversal_of_id' => $transaction->getKey(),
                    'type' => EmployeeFinancingTransactionType::Reversal,
                    'effective_date' => $reversalDate,
                    'principal_amount' => $transaction->principal_amount,
                    'finance_charge_amount' => $transaction->finance_charge_amount,
                    'total_amount' => $transaction->total_amount,
                    'idempotency_key' => "final-settlement:{$settlement->getKey()}:reversal:{$transaction->getKey()}",
                    'reason' => $reason,
                    'created_by_id' => $actor->getKey(),
                ]);
                $financing->update(['status' => EmployeeFinancingStatus::Active, 'settled_at' => null]);
            }
            $settlement->update([
                'status' => FinalSettlementStatus::Reversed,
                'reversal_journal_entry_id' => $reversal->getKey(),
                'reversed_by_id' => $actor->getKey(),
                'reversed_at' => now(),
            ]);
            activity('final_settlements')->causedBy($actor)->performedOn($settlement)->event('reversed')
                ->withProperties([
                    'company_id' => $settlement->company_id,
                    'reversal_journal_entry_id' => $reversal->getKey(),
                    'reason' => $reason,
                ])->log('reversed final settlement');

            return $settlement->refresh();
        }, 3);
    }
}
