<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\PayrollRun;
use Illuminate\Support\Collection;

class PayrollReconciliationReport
{
    /** @return Collection<int, array<string, mixed>> */
    public function forCompany(Company $company): Collection
    {
        return PayrollRun::query()->whereBelongsTo($company)
            ->whereNotNull('journal_entry_id')
            ->with(['entries', 'journalEntry'])
            ->orderByDesc('period_end')->get()
            ->map(function (PayrollRun $run): array {
                $expense = number_format($run->entries->sum(
                    fn ($entry): float => (float) $entry->expenseBasis(),
                ), 4, '.', '');
                $net = number_format($run->total('net_salary'), 4, '.', '');
                $settled = number_format((float) $net - (float) $run->settlementOpenAmount(), 4, '.', '');

                return [
                    'run' => $run,
                    'payroll_expense' => $expense,
                    'net_salary' => $net,
                    'journal_debit' => (string) $run->journalEntry->debit_total,
                    'journal_credit' => (string) $run->journalEntry->credit_total,
                    'settled' => $settled,
                    'open' => $run->settlementOpenAmount(),
                    'reconciled' => bccomp($expense, (string) $run->journalEntry->debit_total, 4) === 0
                        && bccomp((string) $run->journalEntry->debit_total, (string) $run->journalEntry->credit_total, 4) === 0,
                ];
            });
    }
}
