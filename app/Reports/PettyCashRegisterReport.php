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

class PettyCashRegisterReport
{
    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     company: Company,
     *     opening_balance: string,
     *     inflow_total: string,
     *     outflow_total: string,
     *     closing_balance: string,
     *     rows: Collection<int, array<string, mixed>>
     * }
     */
    public function forCompany(Company $company, CarbonInterface $from, CarbonInterface $to): array
    {
        $mapping = AccountingMapping::where('company_id', $company->getKey())
            ->where('system_key', AccountingMappingKey::SitePettyCash)
            ->where('is_active', true)
            ->first();

        if ($mapping === null || $mapping->account === null) {
            throw ValidationException::withMessages(['mapping' => 'Active Site Petty Cash account mapping is required.']);
        }

        $accountId = $mapping->account_id;

        // 1. Opening Balance prior to from date
        $priorLines = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                ->whereDate('transaction_date', '<', $from));

        $opening = bcsub(
            (string) (clone $priorLines)->sum('debit'),
            (string) (clone $priorLines)->sum('credit'),
            4
        );

        // 2. Lines in period
        $periodLines = JournalLine::query()
            ->where('company_id', $company->getKey())
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                ->whereDate('transaction_date', '>=', $from)
                ->whereDate('transaction_date', '<=', $to))
            ->with(['journalEntry.lines.account', 'project', 'relatedCompany', 'party'])
            ->get()
            ->sortBy(fn (JournalLine $line): string => $line->journalEntry->transaction_date->format('Y-m-d').'-'.str_pad((string) $line->id, 8, '0', STR_PAD_LEFT))
            ->values();

        $runningBalance = $opening;
        $totalInflows = '0.0000';
        $totalOutflows = '0.0000';
        $rows = collect();

        foreach ($periodLines as $line) {
            $isInflow = bccomp((string) $line->debit, '0.0000', 4) > 0;
            $amount = $isInflow ? (string) $line->debit : (string) $line->credit;

            if ($isInflow) {
                $totalInflows = bcadd($totalInflows, $amount, 4);
                $runningBalance = bcadd($runningBalance, $amount, 4);
            } else {
                $totalOutflows = bcadd($totalOutflows, $amount, 4);
                $runningBalance = bcsub($runningBalance, $amount, 4);
            }

            // Find contra line for category/head display
            $contraLine = $line->journalEntry->lines->firstWhere('id', '!=', $line->id);

            $rows->push([
                'id' => $line->id,
                'date' => $line->journalEntry->transaction_date->format('Y-m-d'),
                'voucher_number' => $line->journalEntry->voucher_number ?? $line->journalEntry->entry_number,
                'voucher_type' => $line->journalEntry->voucher_type?->label() ?? 'Voucher',
                'description' => $line->description ?: $line->journalEntry->description,
                'category_head' => $contraLine?->account?->name ?? $contraLine?->account_name_snapshot ?? '-',
                'category_code' => $contraLine?->account?->code ?? $contraLine?->account_code_snapshot ?? '-',
                'project' => $line->project?->name ?? $contraLine?->project?->name,
                'expense_of' => $line->relatedCompany?->name ?? $contraLine?->relatedCompany?->name,
                'inflow' => $isInflow ? $amount : '0.0000',
                'outflow' => ! $isInflow ? $amount : '0.0000',
                'running_balance' => $runningBalance,
            ]);
        }

        $closing = $runningBalance;

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'company' => $company,
            'opening_balance' => $opening,
            'inflow_total' => $totalInflows,
            'outflow_total' => $totalOutflows,
            'closing_balance' => $closing,
            'rows' => $rows,
        ];
    }
}
