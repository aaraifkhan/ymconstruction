<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingInstallmentStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingTransactionType;
use App\Enums\TreasuryTransactionType;
use App\Models\EmployeeFinancingInstallment;
use App\Models\EmployeeFinancingTransaction;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReverseEmployeeFinancingTreasuryAction
{
    public function handle(TreasuryTransaction $treasury, User $actor, string $reason): void
    {
        if ($treasury->employee_financing_id === null) {
            return;
        }
        DB::transaction(function () use ($treasury, $actor, $reason): void {
            $financing = $treasury->employeeFinancing()->lockForUpdate()->firstOrFail();
            $transactions = EmployeeFinancingTransaction::query()
                ->where('treasury_transaction_id', $treasury->getKey())
                ->whereNotIn('id', EmployeeFinancingTransaction::query()->whereNotNull('reversal_of_id')->select('reversal_of_id'))
                ->lockForUpdate()->get();
            foreach ($transactions as $transaction) {
                if ($transaction->installment !== null) {
                    $installment = EmployeeFinancingInstallment::query()->whereKey($transaction->installment)->lockForUpdate()->firstOrFail();
                    $principalRecovered = bcsub((string) $installment->principal_recovered, (string) $transaction->principal_amount, 4);
                    $financeChargeRecovered = bcsub((string) $installment->finance_charge_recovered, (string) $transaction->finance_charge_amount, 4);
                    $remainingApplied = bcadd(
                        bcadd($principalRecovered, $financeChargeRecovered, 4),
                        bcadd((string) $installment->principal_waived, (string) $installment->finance_charge_waived, 4),
                        4,
                    );
                    EmployeeFinancingInstallment::query()->whereKey($installment)->update([
                        'principal_recovered' => $principalRecovered,
                        'finance_charge_recovered' => $financeChargeRecovered,
                        'status' => bccomp($remainingApplied, '0', 4) === 1
                            ? EmployeeFinancingInstallmentStatus::Partial->value
                            : EmployeeFinancingInstallmentStatus::Pending->value,
                        'updated_at' => now(),
                    ]);
                }
                $financing->transactions()->create([
                    'company_id' => $financing->company_id,
                    'employee_financing_installment_id' => $transaction->employee_financing_installment_id,
                    'treasury_transaction_id' => $treasury->getKey(),
                    'reversal_of_id' => $transaction->getKey(),
                    'type' => EmployeeFinancingTransactionType::Reversal,
                    'effective_date' => now()->toDateString(),
                    'principal_amount' => $transaction->principal_amount,
                    'finance_charge_amount' => $transaction->finance_charge_amount,
                    'total_amount' => $transaction->total_amount,
                    'idempotency_key' => "treasury:{$treasury->getKey()}:reversal:{$transaction->getKey()}",
                    'reason' => $reason,
                    'created_by_id' => $actor->getKey(),
                ]);
            }
            $financing->update($treasury->type === TreasuryTransactionType::Payment
                ? ['status' => EmployeeFinancingStatus::Approved, 'disbursed_at' => null]
                : ['status' => EmployeeFinancingStatus::Active, 'settled_at' => null]);
        }, 3);
    }
}
