<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingInstallmentStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingTransactionType;
use App\Models\EmployeeFinancing;
use App\Models\EmployeeFinancingInstallment;
use App\Models\JournalEntry;
use App\Models\PayrollEntry;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApplyEmployeeFinancingRecoveryAction
{
    public function handle(
        EmployeeFinancing $financing,
        string $amount,
        EmployeeFinancingTransactionType $type,
        string $idempotencyKey,
        CarbonImmutable $effectiveDate,
        ?User $actor = null,
        ?TreasuryTransaction $treasuryTransaction = null,
        ?PayrollEntry $payrollEntry = null,
        ?JournalEntry $journalEntry = null,
        ?string $reason = null,
        bool $authorize = true,
    ): EmployeeFinancing {
        if ($actor !== null && $authorize) {
            Gate::forUser($actor)->authorize($type === EmployeeFinancingTransactionType::Waiver ? 'waive' : 'recover', $financing);
        }

        return DB::transaction(function () use (
            $financing, $amount, $type, $idempotencyKey, $effectiveDate,
            $actor, $treasuryTransaction, $payrollEntry, $journalEntry, $reason,
        ): EmployeeFinancing {
            $financing = EmployeeFinancing::query()->whereKey($financing)->lockForUpdate()->firstOrFail();
            if ($financing->status !== EmployeeFinancingStatus::Active || bccomp($amount, '0', 4) !== 1) {
                throw ValidationException::withMessages(['amount' => 'A positive recovery may only be applied to active financing.']);
            }
            if ($financing->transactions()->where('idempotency_key', 'like', $idempotencyKey.'%')->exists()) {
                return $financing;
            }
            if (bccomp($amount, $financing->outstandingAmount(), 4) === 1) {
                throw ValidationException::withMessages(['amount' => 'Recovery cannot exceed the financing outstanding balance.']);
            }

            $remaining = $amount;
            $installments = EmployeeFinancingInstallment::query()
                ->where('employee_financing_id', $financing->getKey())
                ->whereNotIn('status', [
                    EmployeeFinancingInstallmentStatus::Paid,
                    EmployeeFinancingInstallmentStatus::Waived,
                    EmployeeFinancingInstallmentStatus::Superseded,
                ])
                ->orderBy('due_date')->orderBy('installment_number')
                ->lockForUpdate()->get();

            foreach ($installments as $installment) {
                if (bccomp($remaining, '0', 4) !== 1) {
                    break;
                }
                $chargeOpen = bcsub(
                    (string) $installment->finance_charge_due,
                    bcadd((string) $installment->finance_charge_recovered, (string) $installment->finance_charge_waived, 4),
                    4,
                );
                $chargeApplied = bccomp($remaining, $chargeOpen, 4) === 1 ? $chargeOpen : $remaining;
                $remaining = bcsub($remaining, $chargeApplied, 4);
                $principalOpen = bcsub(
                    (string) $installment->principal_due,
                    bcadd((string) $installment->principal_recovered, (string) $installment->principal_waived, 4),
                    4,
                );
                $principalApplied = bccomp($remaining, $principalOpen, 4) === 1 ? $principalOpen : $remaining;
                $remaining = bcsub($remaining, $principalApplied, 4);
                $applied = bcadd($principalApplied, $chargeApplied, 4);
                if (bccomp($applied, '0', 4) !== 1) {
                    continue;
                }

                $isWaiver = $type === EmployeeFinancingTransactionType::Waiver;
                EmployeeFinancingInstallment::query()->whereKey($installment)->update([
                    $isWaiver ? 'principal_waived' : 'principal_recovered' => bcadd(
                        (string) ($isWaiver ? $installment->principal_waived : $installment->principal_recovered),
                        $principalApplied,
                        4,
                    ),
                    $isWaiver ? 'finance_charge_waived' : 'finance_charge_recovered' => bcadd(
                        (string) ($isWaiver ? $installment->finance_charge_waived : $installment->finance_charge_recovered),
                        $chargeApplied,
                        4,
                    ),
                    'status' => bccomp($applied, $installment->outstandingAmount(), 4) === 0
                        ? ($isWaiver ? EmployeeFinancingInstallmentStatus::Waived->value : EmployeeFinancingInstallmentStatus::Paid->value)
                        : EmployeeFinancingInstallmentStatus::Partial->value,
                    'updated_at' => now(),
                ]);
                $financing->transactions()->create([
                    'company_id' => $financing->company_id,
                    'employee_financing_installment_id' => $installment->getKey(),
                    'treasury_transaction_id' => $treasuryTransaction?->getKey(),
                    'payroll_entry_id' => $payrollEntry?->getKey(),
                    'journal_entry_id' => $journalEntry?->getKey(),
                    'type' => $type,
                    'effective_date' => $effectiveDate,
                    'principal_amount' => $principalApplied,
                    'finance_charge_amount' => $chargeApplied,
                    'total_amount' => $applied,
                    'idempotency_key' => "{$idempotencyKey}:{$installment->getKey()}",
                    'reason' => $reason,
                    'created_by_id' => $actor?->getKey(),
                ]);
            }

            if (bccomp($remaining, '0', 4) !== 0) {
                throw ValidationException::withMessages(['amount' => 'Recovery could not be reconciled to the active schedule.']);
            }
            if (bccomp($financing->refresh()->outstandingAmount(), '0', 4) === 0) {
                $financing->update(['status' => EmployeeFinancingStatus::Settled, 'settled_at' => now()]);
            }
            activity('employee_financings')->causedBy($actor)->performedOn($financing)->event($type->value)
                ->withProperties(['company_id' => $financing->company_id, 'amount' => $amount])
                ->log("recorded employee financing {$type->value}");

            return $financing->refresh();
        }, 3);
    }
}
