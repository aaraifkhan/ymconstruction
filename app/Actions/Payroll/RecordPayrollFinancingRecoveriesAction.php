<?php

namespace App\Actions\Payroll;

use App\Actions\HR\ApplyEmployeeFinancingRecoveryAction;
use App\Enums\EmployeeFinancingTransactionType;
use App\Enums\PayrollComponentType;
use App\Models\EmployeeFinancing;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RecordPayrollFinancingRecoveriesAction
{
    public function __construct(private ApplyEmployeeFinancingRecoveryAction $applyRecovery) {}

    public function handle(PayrollRun $run, JournalEntry $journal, User $actor): void
    {
        $run->loadMissing('entries.components');
        foreach ($run->entries as $entry) {
            foreach ($entry->components->whereIn('type', [
                PayrollComponentType::LoanInstallment,
                PayrollComponentType::AdvanceRecovery,
            ]) as $component) {
                if ($component->source_type !== EmployeeFinancing::class || $component->source_id === null) {
                    throw ValidationException::withMessages(['payroll_component' => 'Financing deduction has an invalid source.']);
                }
                $financing = EmployeeFinancing::query()->whereKey($component->source_id)
                    ->where('company_id', $run->company_id)
                    ->where('employment_id', $entry->employment_id)->lockForUpdate()->firstOrFail();
                if (bccomp((string) $component->amount, $financing->dueAmountOnOrBefore($run->period_end), 4) === 1) {
                    throw ValidationException::withMessages([
                        'loan_advance_deduction' => "Payroll recovery exceeds approved due amount for {$financing->reference_number}. Regenerate Payroll.",
                    ]);
                }
                $this->applyRecovery->handle(
                    $financing,
                    (string) $component->amount,
                    EmployeeFinancingTransactionType::PayrollRecovery,
                    "payroll:{$run->getKey()}:entry:{$entry->getKey()}:financing:{$financing->getKey()}",
                    CarbonImmutable::instance($run->period_end),
                    $actor,
                    payrollEntry: $entry,
                    journalEntry: $journal,
                    reason: "Payroll recovery {$run->reference_number}",
                );
            }
        }
    }
}
