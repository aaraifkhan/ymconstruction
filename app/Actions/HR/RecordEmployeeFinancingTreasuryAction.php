<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingTransactionType;
use App\Enums\TreasuryTransactionType;
use App\Models\EmployeeFinancing;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RecordEmployeeFinancingTreasuryAction
{
    public function __construct(private ApplyEmployeeFinancingRecoveryAction $applyRecovery) {}

    public function handle(TreasuryTransaction $treasury, User $actor): void
    {
        if ($treasury->employee_financing_id === null) {
            return;
        }

        $financing = EmployeeFinancing::query()->whereKey($treasury->employee_financing_id)->lockForUpdate()->firstOrFail();
        if ((int) $financing->company_id !== (int) $treasury->company_id
            || (int) $financing->employment_id !== (int) $treasury->employment_id) {
            throw ValidationException::withMessages(['employee_financing_id' => 'Treasury financing link must match company and Employment.']);
        }

        if ($treasury->type === TreasuryTransactionType::Payment) {
            if ($financing->status !== EmployeeFinancingStatus::DisbursementPending
                || bccomp((string) $treasury->amount, (string) $financing->principal_amount, 4) !== 0) {
                throw ValidationException::withMessages(['amount' => 'Disbursement must match the approved principal and pending financing.']);
            }
            $financing->transactions()->firstOrCreate(
                ['idempotency_key' => "treasury:{$treasury->getKey()}:disbursement"],
                [
                    'company_id' => $financing->company_id,
                    'treasury_transaction_id' => $treasury->getKey(),
                    'type' => EmployeeFinancingTransactionType::Disbursement,
                    'effective_date' => $treasury->transaction_date,
                    'principal_amount' => $treasury->amount,
                    'finance_charge_amount' => 0,
                    'total_amount' => $treasury->amount,
                    'created_by_id' => $actor->getKey(),
                ],
            );
            $financing->update([
                'status' => EmployeeFinancingStatus::Active,
                'disbursed_at' => now(),
            ]);

            return;
        }

        $this->applyRecovery->handle(
            $financing,
            (string) $treasury->amount,
            EmployeeFinancingTransactionType::TreasuryRecovery,
            "treasury:{$treasury->getKey()}:recovery",
            CarbonImmutable::parse($treasury->transaction_date),
            $actor,
            $treasury,
        );
    }
}
