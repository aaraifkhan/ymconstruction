<?php

namespace App\Reports;

use App\Enums\JournalStatus;
use App\Models\BankStatementLine;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UnreconciledBankItemReport
{
    /** @return array{statement_items:Collection<int, array{line:BankStatementLine, unmatched_amount:string}>, book_items:Collection<int, array{line:JournalLine, unmatched_amount:string}>} */
    public function forBank(
        Company $company,
        CompanyBankAccount $bankAccount,
        CarbonInterface $to,
    ): array {
        if ((int) $bankAccount->company_id !== (int) $company->getKey()) {
            throw ValidationException::withMessages(['company_bank_account_id' => 'Bank account must belong to the report company.']);
        }

        $statementItems = BankStatementLine::query()
            ->whereBelongsTo($company)
            ->where('company_bank_account_id', $bankAccount->getKey())
            ->whereDate('transaction_date', '<=', $to)
            ->withSum('reconciliationMatches as matched_amount', 'amount')
            ->orderBy('transaction_date')->get()
            ->map(fn (BankStatementLine $line): array => [
                'line' => $line,
                'unmatched_amount' => bcsub($line->statementAmount(), (string) ($line->matched_amount ?? 0), 4),
            ])->filter(fn (array $row): bool => bccomp($row['unmatched_amount'], '0', 4) === 1)->values();

        $bookItems = JournalLine::query()->whereBelongsTo($company)
            ->where('company_bank_account_id', $bankAccount->getKey())
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                ->whereDate('transaction_date', '<=', $to))
            ->with(['journalEntry'])
            ->withSum('bankReconciliationMatches as matched_amount', 'amount')
            ->get()->map(function (JournalLine $line): array {
                $amount = bccomp((string) $line->debit, '0', 4) === 1 ? (string) $line->debit : (string) $line->credit;

                return [
                    'line' => $line,
                    'unmatched_amount' => bcsub($amount, (string) ($line->matched_amount ?? 0), 4),
                ];
            })->filter(fn (array $row): bool => bccomp($row['unmatched_amount'], '0', 4) === 1)->values();

        return ['statement_items' => $statementItems, 'book_items' => $bookItems];
    }
}
