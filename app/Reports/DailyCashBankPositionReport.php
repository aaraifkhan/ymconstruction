<?php

namespace App\Reports;

use App\Enums\AccountingMappingKey;
use App\Enums\JournalStatus;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyCashBankPositionReport
{
    /**
     * @return array{
     *     date: string,
     *     company: Company,
     *     funds: array<int, array{
     *         account_id: int,
     *         name: string,
     *         code: string,
     *         type: string,
     *         opening_balance: string,
     *         receipts_total: string,
     *         payments_total: string,
     *         closing_balance: string
     *     }>,
     *     totals: array{
     *         opening_balance: string,
     *         receipts_total: string,
     *         grand_total: string,
     *         payments_total: string,
     *         closing_balance: string
     *     },
     *     receipt_items: Collection<int, array<string, mixed>>,
     *     payment_items: Collection<int, array<string, mixed>>
     * }
     */
    public function forCompany(Company $company, CarbonInterface $date): array
    {
        $mappings = AccountingMapping::query()
            ->whereBelongsTo($company)
            ->where(function ($query): void {
                $query->whereIn('system_key', [
                    AccountingMappingKey::DefaultCash,
                    AccountingMappingKey::SitePettyCash,
                    AccountingMappingKey::DirectorCashAdvance,
                ])->orWhereNotNull('company_bank_account_id');
            })
            ->where('is_active', true)
            ->with(['account', 'bankAccount'])
            ->get();

        $funds = [];
        $totalOpening = '0.0000';
        $totalReceipts = '0.0000';
        $totalPayments = '0.0000';
        $totalClosing = '0.0000';

        $receiptItems = collect();
        $paymentItems = collect();

        foreach ($mappings as $mapping) {
            $account = $mapping->account;
            if ($account === null) {
                continue;
            }

            $fundName = match (true) {
                $mapping->bankAccount !== null => $mapping->bankAccount->bank_name.($mapping->bankAccount->account_title ? " ({$mapping->bankAccount->account_title})" : ''),
                $mapping->system_key === AccountingMappingKey::DefaultCash => 'Head Office Cash',
                $mapping->system_key === AccountingMappingKey::SitePettyCash => 'Site Petty Cash',
                $mapping->system_key === AccountingMappingKey::DirectorCashAdvance => 'Director Cash / Float',
                default => $account->name,
            };

            $fundType = match (true) {
                $mapping->bankAccount !== null => 'bank',
                $mapping->system_key === AccountingMappingKey::SitePettyCash => 'petty_cash',
                $mapping->system_key === AccountingMappingKey::DirectorCashAdvance => 'director',
                default => 'cash',
            };

            // Historical balance prior to the date
            $priorLines = JournalLine::query()
                ->where('company_id', $company->getKey())
                ->where('account_id', $account->getKey())
                ->whereHas('journalEntry', fn ($query) => $query
                    ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                    ->whereDate('transaction_date', '<', $date));

            $opening = bcsub(
                (string) (clone $priorLines)->sum('debit'),
                (string) (clone $priorLines)->sum('credit'),
                4
            );

            // Day's transaction lines
            $dayLines = JournalLine::query()
                ->where('company_id', $company->getKey())
                ->where('account_id', $account->getKey())
                ->whereHas('journalEntry', fn ($query) => $query
                    ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                    ->whereDate('transaction_date', $date))
                ->with(['journalEntry', 'party', 'project'])
                ->get();

            $dayReceipts = '0.0000';
            $dayPayments = '0.0000';

            foreach ($dayLines as $line) {
                if (bccomp((string) $line->debit, '0.0000', 4) > 0) {
                    $dayReceipts = bcadd($dayReceipts, (string) $line->debit, 4);
                    $receiptItems->push([
                        'time' => $line->journalEntry->created_at?->format('H:i') ?? '-',
                        'voucher_number' => $line->journalEntry->voucher_number ?? $line->journalEntry->entry_number,
                        'voucher_type' => $line->journalEntry->voucher_type?->label() ?? 'Journal',
                        'fund_name' => $fundName,
                        'fund_code' => $account->code,
                        'particulars' => $line->narration ?: $line->journalEntry->narration ?: 'Funds Received',
                        'party' => $line->party?->name,
                        'project' => $line->project?->name,
                        'amount' => (string) $line->debit,
                    ]);
                }

                if (bccomp((string) $line->credit, '0.0000', 4) > 0) {
                    $dayPayments = bcadd($dayPayments, (string) $line->credit, 4);
                    $paymentItems->push([
                        'time' => $line->journalEntry->created_at?->format('H:i') ?? '-',
                        'voucher_number' => $line->journalEntry->voucher_number ?? $line->journalEntry->entry_number,
                        'voucher_type' => $line->journalEntry->voucher_type?->label() ?? 'Payment',
                        'fund_name' => $fundName,
                        'fund_code' => $account->code,
                        'particulars' => $line->narration ?: $line->journalEntry->narration ?: 'Disbursement',
                        'party' => $line->party?->name,
                        'project' => $line->project?->name,
                        'amount' => (string) $line->credit,
                    ]);
                }
            }

            $closing = bcsub(bcadd($opening, $dayReceipts, 4), $dayPayments, 4);

            $funds[] = [
                'account_id' => $account->getKey(),
                'name' => $fundName,
                'code' => $account->code,
                'type' => $fundType,
                'opening_balance' => $opening,
                'receipts_total' => $dayReceipts,
                'payments_total' => $dayPayments,
                'closing_balance' => $closing,
            ];

            $totalOpening = bcadd($totalOpening, $opening, 4);
            $totalReceipts = bcadd($totalReceipts, $dayReceipts, 4);
            $totalPayments = bcadd($totalPayments, $dayPayments, 4);
            $totalClosing = bcadd($totalClosing, $closing, 4);
        }

        $grandTotal = bcadd($totalOpening, $totalReceipts, 4);

        return [
            'date' => $date->toDateString(),
            'company' => $company,
            'funds' => $funds,
            'totals' => [
                'opening_balance' => $totalOpening,
                'receipts_total' => $totalReceipts,
                'grand_total' => $grandTotal,
                'payments_total' => $totalPayments,
                'closing_balance' => $totalClosing,
            ],
            'receipt_items' => $receiptItems,
            'payment_items' => $paymentItems,
        ];
    }
}
