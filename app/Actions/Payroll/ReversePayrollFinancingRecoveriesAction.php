<?php

namespace App\Actions\Payroll;

use App\Enums\EmployeeFinancingInstallmentStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingTransactionType;
use App\Models\EmployeeFinancing;
use App\Models\EmployeeFinancingInstallment;
use App\Models\EmployeeFinancingTransaction;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\User;

class ReversePayrollFinancingRecoveriesAction
{
    public function handle(PayrollRun $run, JournalEntry $reversalJournal, User $actor, string $reason): void
    {
        $entryIds = $run->entries()->pluck('id');
        $transactions = EmployeeFinancingTransaction::query()
            ->whereIn('payroll_entry_id', $entryIds)
            ->where('type', EmployeeFinancingTransactionType::PayrollRecovery)
            ->whereNotIn('id', EmployeeFinancingTransaction::query()->whereNotNull('reversal_of_id')->select('reversal_of_id'))
            ->lockForUpdate()->get();

        foreach ($transactions as $transaction) {
            $financing = EmployeeFinancing::query()->whereKey($transaction->employee_financing_id)
                ->lockForUpdate()->firstOrFail();
            $installment = EmployeeFinancingInstallment::query()
                ->whereKey($transaction->employee_financing_installment_id)->lockForUpdate()->firstOrFail();
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
            $financing->transactions()->create([
                'company_id' => $financing->company_id,
                'employee_financing_installment_id' => $installment->getKey(),
                'payroll_entry_id' => $transaction->payroll_entry_id,
                'journal_entry_id' => $reversalJournal->getKey(),
                'reversal_of_id' => $transaction->getKey(),
                'type' => EmployeeFinancingTransactionType::Reversal,
                'effective_date' => $reversalJournal->transaction_date,
                'principal_amount' => $transaction->principal_amount,
                'finance_charge_amount' => $transaction->finance_charge_amount,
                'total_amount' => $transaction->total_amount,
                'idempotency_key' => "payroll:{$run->getKey()}:reversal:{$transaction->getKey()}",
                'reason' => $reason,
                'created_by_id' => $actor->getKey(),
            ]);
            $financing->update(['status' => EmployeeFinancingStatus::Active, 'settled_at' => null]);
        }
    }
}
