<?php

namespace App\Reports;

use App\Enums\AccountingMappingKey;
use App\Enums\JournalStatus;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DirectorExpenseLedgerReport
{
    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     company: Company,
     *     opening_due_to_director: string,
     *     total_funded_by_director: string,
     *     total_reimbursed: string,
     *     closing_due_to_director: string,
     *     categories_summary: array<string, array{label: string, count: int, total: string}>,
     *     rows: Collection<int, array<string, mixed>>
     * }
     */
    public function forCompany(Company $company, CarbonInterface $from, CarbonInterface $to): array
    {
        $mapping = AccountingMapping::where('company_id', $company->getKey())
            ->where('system_key', AccountingMappingKey::DirectorLoan)
            ->where('is_active', true)
            ->first();

        if ($mapping === null || $mapping->account === null) {
            throw ValidationException::withMessages(['mapping' => 'Active Director Loan (Due to Director) mapping is required.']);
        }

        $directorAccountId = $mapping->account_id;

        // Opening Balance prior to from date
        // For a liability account (Director Loan), Credit is positive (Due to Director), Debit is payment/reimbursement to Director.
        $priorLines = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->where('account_id', $directorAccountId)
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                ->whereDate('transaction_date', '<', $from));

        $openingDue = bcsub(
            (string) (clone $priorLines)->sum('credit'),
            (string) (clone $priorLines)->sum('debit'),
            4
        );

        // Period lines
        $periodLines = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->where('account_id', $directorAccountId)
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                ->whereDate('transaction_date', '>=', $from)
                ->whereDate('transaction_date', '<=', $to))
            ->with(['journalEntry.lines.account', 'project', 'party'])
            ->get()
            ->sortBy(fn (JournalLine $line): string => $line->journalEntry->transaction_date->format('Y-m-d').'-'.str_pad((string) $line->id, 8, '0', STR_PAD_LEFT))
            ->values();

        $runningDue = $openingDue;
        $totalFunded = '0.0000';
        $totalReimbursed = '0.0000';
        $categoriesSummary = [];
        $rows = collect();

        foreach ($periodLines as $line) {
            $isFundedByDirector = bccomp((string) $line->credit, '0.0000', 4) > 0;
            $amount = $isFundedByDirector ? (string) $line->credit : (string) $line->debit;

            if ($isFundedByDirector) {
                $totalFunded = bcadd($totalFunded, $amount, 4);
                $runningDue = bcadd($runningDue, $amount, 4);
            } else {
                $totalReimbursed = bcadd($totalReimbursed, $amount, 4);
                $runningDue = bcsub($runningDue, $amount, 4);
            }

            // Find contra line for category/expense head
            $contraLine = $line->journalEntry->lines->firstWhere('id', '!=', $line->id);
            $categoryName = $contraLine?->account?->name ?? $contraLine?->account_name_snapshot ?? 'General Advance / Settlement';
            $categoryCode = $contraLine?->account?->code ?? $contraLine?->account_code_snapshot ?? '-';

            if ($isFundedByDirector) {
                if (! isset($categoriesSummary[$categoryCode])) {
                    $categoriesSummary[$categoryCode] = [
                        'label' => $categoryName,
                        'count' => 0,
                        'total' => '0.0000',
                    ];
                }
                $categoriesSummary[$categoryCode]['count']++;
                $categoriesSummary[$categoryCode]['total'] = bcadd($categoriesSummary[$categoryCode]['total'], $amount, 4);
            }

            $rows->push([
                'id' => $line->id,
                'date' => $line->journalEntry->transaction_date->format('Y-m-d'),
                'voucher_number' => $line->journalEntry->voucher_number ?? $line->journalEntry->entry_number,
                'description' => $line->description ?: $line->journalEntry->description,
                'category' => $categoryName,
                'category_code' => $categoryCode,
                'project' => $line->project?->name ?? $contraLine?->project?->name,
                'reimbursed_debit' => ! $isFundedByDirector ? $amount : '0.0000',
                'funded_credit' => $isFundedByDirector ? $amount : '0.0000',
                'net_due' => $runningDue,
            ]);
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'company' => $company,
            'opening_due_to_director' => $openingDue,
            'total_funded_by_director' => $totalFunded,
            'total_reimbursed' => $totalReimbursed,
            'closing_due_to_director' => $runningDue,
            'categories_summary' => $categoriesSummary,
            'rows' => $rows,
        ];
    }
}
