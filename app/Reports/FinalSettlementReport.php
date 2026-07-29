<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\FinalSettlement;
use Illuminate\Support\Collection;

class FinalSettlementReport
{
    /** @return Collection<int, array<string, mixed>> */
    public function forCompany(Company $company): Collection
    {
        return FinalSettlement::query()
            ->whereBelongsTo($company)
            ->with(['employment.employee', 'journalEntry', 'treasuryAllocations.treasuryTransaction'])
            ->orderBy('cutoff_date')
            ->get()
            ->map(function (FinalSettlement $settlement): array {
                $postedTreasury = $settlement->treasuryAllocations
                    ->filter(fn ($allocation): bool => $allocation->treasuryTransaction?->status->value === 'posted')
                    ->reduce(fn (string $total, $allocation): string => bcadd(
                        $total,
                        (string) $allocation->amount,
                        4,
                    ), '0.0000');
                $glAmount = $settlement->journalEntry === null
                    ? '0.0000'
                    : (string) $settlement->journalEntry->debit_total;

                return [
                    'reference_number' => $settlement->reference_number,
                    'employee_code' => $settlement->employment->employee_code,
                    'employee_name' => $settlement->employment->employee->full_name,
                    'cutoff_date' => $settlement->cutoff_date->toDateString(),
                    'status' => $settlement->status->value,
                    'earning_total' => (string) $settlement->earning_total,
                    'recovery_total' => (string) $settlement->recovery_total,
                    'balance_direction' => $settlement->balance_direction,
                    'net_amount' => (string) $settlement->net_amount,
                    'gl_voucher' => $settlement->journalEntry?->voucher_number,
                    'gl_amount' => $glAmount,
                    'treasury_settled' => $postedTreasury,
                    'open_amount' => $settlement->postedOpenAmount(),
                    'operational_gl_reconciled' => $settlement->journalEntry === null
                        || bccomp(
                            bcadd((string) $settlement->earning_total, $settlement->balance_direction === 'receivable'
                                ? (string) $settlement->net_amount : '0.0000', 4),
                            (string) $settlement->journalEntry->debit_total,
                            4,
                        ) === 0,
                    'treasury_reconciled' => bccomp(
                        bcadd($postedTreasury, $settlement->postedOpenAmount(), 4),
                        (string) $settlement->net_amount,
                        4,
                    ) === 0,
                ];
            });
    }

    /** @return array<string, mixed> */
    public function settlementLetter(FinalSettlement $settlement): array
    {
        $settlement->loadMissing(['company', 'employment.employee', 'separation', 'lines']);

        return [
            'company_name' => $settlement->company->legal_name ?: $settlement->company->name,
            'reference_number' => $settlement->reference_number,
            'employee_name' => $settlement->employment->employee->full_name,
            'employee_code' => $settlement->employment->employee_code,
            'last_working_date' => $settlement->cutoff_date->toDateString(),
            'separation_type' => str($settlement->separation->type->value)->headline()->toString(),
            'lines' => $settlement->lines->map(fn ($line): array => [
                'component' => $line->component_type->label(),
                'nature' => $line->nature->label(),
                'description' => $line->description,
                'amount' => (string) $line->amount,
            ])->all(),
            'earning_total' => (string) $settlement->earning_total,
            'recovery_total' => (string) $settlement->recovery_total,
            'balance_direction' => $settlement->balance_direction,
            'net_amount' => (string) $settlement->net_amount,
            'approved_at' => $settlement->approved_at?->toIso8601String(),
            'source_checksum' => $settlement->source_checksum,
        ];
    }
}
