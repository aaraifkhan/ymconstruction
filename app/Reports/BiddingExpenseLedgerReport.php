<?php

namespace App\Reports;

use App\Enums\JournalStatus;
use App\Models\Company;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BiddingExpenseLedgerReport
{
    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     company: Company,
     *     total_bidding_expense: string,
     *     subhead_summary: array<string, array{label: string, count: int, total: string}>,
     *     rows: Collection<int, array<string, mixed>>
     * }
     */
    public function forCompany(Company $company, CarbonInterface $from, CarbonInterface $to): array
    {
        $lines = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->where('account_code_snapshot', 'LIKE', '505%')
            ->where('debit', '>', 0)
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                ->whereDate('transaction_date', '>=', $from)
                ->whereDate('transaction_date', '<=', $to))
            ->with(['journalEntry.lines.account', 'party'])
            ->get()
            ->sortBy(fn (JournalLine $line): string => $line->journalEntry->transaction_date->format('Y-m-d').'-'.str_pad((string) $line->id, 8, '0', STR_PAD_LEFT))
            ->values();

        $totalExpense = '0.0000';
        $subheadSummary = [];
        $rows = collect();

        foreach ($lines as $line) {
            $amount = (string) $line->debit;
            $totalExpense = bcadd($totalExpense, $amount, 4);

            $code = $line->account_code_snapshot;
            $name = $line->account_name_snapshot;

            if (! isset($subheadSummary[$code])) {
                $subheadSummary[$code] = [
                    'label' => $name,
                    'count' => 0,
                    'total' => '0.0000',
                ];
            }
            $subheadSummary[$code]['count']++;
            $subheadSummary[$code]['total'] = bcadd($subheadSummary[$code]['total'], $amount, 4);

            // Paid from fund account (contra line)
            $contraLine = $line->journalEntry->lines->firstWhere('id', '!=', $line->id);
            $paidFrom = $contraLine?->account?->name ?? $contraLine?->account_name_snapshot ?? 'Cash / Bank';

            $rows->push([
                'id' => $line->id,
                'date' => $line->journalEntry->transaction_date->format('Y-m-d'),
                'voucher_number' => $line->journalEntry->voucher_number ?? $line->journalEntry->entry_number,
                'description' => $line->description ?: $line->journalEntry->description,
                'subhead' => $name,
                'subhead_code' => $code,
                'paid_from' => $paidFrom,
                'party' => $line->party?->name,
                'amount' => $amount,
            ]);
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'company' => $company,
            'total_bidding_expense' => $totalExpense,
            'subhead_summary' => $subheadSummary,
            'rows' => $rows,
        ];
    }
}
