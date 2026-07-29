<?php

namespace App\Actions\Treasury;

use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Actions\HR\ReverseEmployeeFinancingTreasuryAction;
use App\Enums\FinalSettlementStatus;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryStatus;
use App\Models\FinalSettlement;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseTreasuryTransactionAction
{
    public function __construct(
        private ReverseJournalEntryAction $reverseJournalEntry,
        private ReverseEmployeeFinancingTreasuryAction $reverseEmployeeFinancing,
    ) {}

    public function handle(
        TreasuryTransaction $transaction,
        User $actor,
        CarbonInterface $reversalDate,
        string $reason,
    ): TreasuryTransaction {
        Gate::forUser($actor)->authorize('reverse', $transaction);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($actor, $reason, $reversalDate, $transaction): TreasuryTransaction {
            $transaction = TreasuryTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if ($transaction->status === TreasuryStatus::Reversed) {
                return $transaction;
            }
            if ($transaction->status !== TreasuryStatus::Posted || $transaction->journal_entry_id === null) {
                throw ValidationException::withMessages(['status' => 'Only a posted treasury transaction may be reversed.']);
            }

            $reversal = $this->reverseJournalEntry->handle(
                $transaction->journalEntry()->firstOrFail(),
                $actor,
                $reversalDate,
                $reason,
            );
            $transaction->update([
                'status' => TreasuryStatus::Reversed,
                'reversal_journal_entry_id' => $reversal->getKey(),
                'reversed_by_id' => $actor->getKey(),
                'reversed_at' => now(),
            ]);
            $this->reverseEmployeeFinancing->handle($transaction, $actor, $reason);
            FinalSettlement::query()->whereIn(
                'id',
                $transaction->allocations()
                    ->where('allocation_type', TreasuryAllocationType::FinalSettlement)
                    ->pluck('allocatable_id'),
            )->where('status', FinalSettlementStatus::Settled)
                ->update(['status' => FinalSettlementStatus::Posted->value, 'updated_at' => now()]);
            activity('treasury_transactions')->causedBy($actor)->performedOn($transaction)->event('reversed')
                ->withProperties([
                    'company_id' => $transaction->company_id,
                    'reversal_journal_entry_id' => $reversal->getKey(),
                    'reason' => $reason,
                ])->log('reversed treasury transaction');

            return $transaction->refresh();
        }, attempts: 3);
    }
}
