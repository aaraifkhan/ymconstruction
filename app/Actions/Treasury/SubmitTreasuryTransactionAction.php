<?php

namespace App\Actions\Treasury;

use App\Enums\TreasuryInstrumentType;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryStatus;
use App\Enums\TreasuryTransactionType;
use App\Models\Account;
use App\Models\CustomerInvoice;
use App\Models\PayrollEntry;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitTreasuryTransactionAction
{
    public function handle(TreasuryTransaction $transaction, User $actor): TreasuryTransaction
    {
        Gate::forUser($actor)->authorize('submit', $transaction);

        return DB::transaction(function () use ($actor, $transaction): TreasuryTransaction {
            $transaction = TreasuryTransaction::query()->whereKey($transaction)->lockForUpdate()->firstOrFail();
            if (! $transaction->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Only draft or rejected treasury transactions may be submitted.']);
            }
            if ((int) $transaction->prepared_by_id !== (int) $actor->getKey()) {
                throw ValidationException::withMessages(['prepared_by_id' => 'Only the recorded preparer may submit this transaction.']);
            }
            if ($transaction->instrument_type === TreasuryInstrumentType::Cheque
                && (blank($transaction->instrument_number) || $transaction->instrument_date === null)) {
                throw ValidationException::withMessages(['instrument_number' => 'Cheque number and date are required.']);
            }

            $allocations = $transaction->allocations()->with('allocatable')->lockForUpdate()->get();
            $allocatedAmount = '0.0000';
            foreach ($allocations as $allocation) {
                if (! $allocation->allocatable instanceof VendorBill
                    && ! $allocation->allocatable instanceof CustomerInvoice
                    && ! $allocation->allocatable instanceof PayrollEntry) {
                    throw ValidationException::withMessages(['allocations' => 'Unsupported open-item allocation source.']);
                }
                $openAmount = $allocation->allocatable->openAmount($transaction->getKey());
                if (bccomp((string) $allocation->amount, $openAmount, 4) === 1) {
                    throw ValidationException::withMessages(['allocations' => 'Allocation exceeds the open-item balance.']);
                }
                $allocatedAmount = bcadd($allocatedAmount, (string) $allocation->amount, 4);
            }

            if (bccomp($allocatedAmount, (string) $transaction->amount, 4) === 1) {
                throw ValidationException::withMessages(['allocations' => 'Total allocations cannot exceed the transaction amount.']);
            }

            $unallocatedAmount = bcsub((string) $transaction->amount, $allocatedAmount, 4);
            $this->validatePurpose($transaction, $allocatedAmount, $unallocatedAmount);

            $transaction->update([
                'status' => TreasuryStatus::Submitted,
                'allocated_amount' => $allocatedAmount,
                'unallocated_amount' => $unallocatedAmount,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'approved_by_id' => null,
                'approved_at' => null,
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            activity('treasury_transactions')->causedBy($actor)->performedOn($transaction)->event('submitted')
                ->withProperties([
                    'company_id' => $transaction->company_id,
                    'type' => $transaction->type->value,
                    'purpose' => $transaction->purpose->value,
                    'amount' => $transaction->amount,
                    'allocated_amount' => $allocatedAmount,
                ])->log('submitted treasury transaction');

            return $transaction->refresh();
        }, attempts: 3);
    }

    private function validatePurpose(
        TreasuryTransaction $transaction,
        string $allocatedAmount,
        string $unallocatedAmount,
    ): void {
        if ($transaction->type === TreasuryTransactionType::Transfer) {
            if (bccomp($allocatedAmount, '0', 4) !== 0) {
                throw ValidationException::withMessages(['allocations' => 'Cash/bank transfers cannot carry open-item allocations.']);
            }

            return;
        }

        if ($transaction->purpose === TreasuryPurpose::Settlement) {
            if (! in_array($transaction->type, [TreasuryTransactionType::Payment, TreasuryTransactionType::Receipt], true)
                || bccomp($unallocatedAmount, '0', 4) !== 0) {
                throw ValidationException::withMessages(['allocations' => 'Settlement requires a fully allocated Vendor, Payroll, or Customer open item.']);
            }

            return;
        }

        if ($transaction->purpose === TreasuryPurpose::Advance) {
            if (bccomp($allocatedAmount, '0', 4) !== 0
                || ($transaction->party_id === null && $transaction->employment_id === null)) {
                throw ValidationException::withMessages(['counterparty_type' => 'An advance requires one counterparty and no open-item allocation.']);
            }

            return;
        }

        if (bccomp($allocatedAmount, '0', 4) !== 0) {
            throw ValidationException::withMessages(['allocations' => 'Refund/other cash operations do not combine with open-item allocations.']);
        }

        $offsetAccountId = $transaction->type === TreasuryTransactionType::Payment
            ? $transaction->destination_account_id
            : $transaction->source_account_id;
        if (! Account::query()->whereKey($offsetAccountId)->where('company_id', $transaction->company_id)
            ->where('is_active', true)->where('allows_manual_posting', true)->exists()) {
            throw ValidationException::withMessages(['offset_account_id' => 'Refund/other cash operations require an active same-company offset account.']);
        }
    }
}
