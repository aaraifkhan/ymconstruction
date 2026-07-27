<?php

namespace App\Reports;

use App\Enums\JournalStatus;
use App\Models\Company;
use App\Models\JournalEntry;

class AccountingRecoveryManifest
{
    /** @return array<string, mixed> */
    public function generate(Company $company): array
    {
        $journals = JournalEntry::query()->where('company_id', $company->getKey())
            ->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed])
            ->orderBy('id')->get(['id', 'voucher_number', 'status', 'debit_total', 'credit_total'])
            ->map(fn (JournalEntry $entry): array => [
                'id' => $entry->getKey(),
                'voucher_number' => $entry->voucher_number,
                'status' => $entry->status->value,
                'debit_total' => (string) $entry->debit_total,
                'credit_total' => (string) $entry->credit_total,
            ])->all();
        $counts = [
            'accounts' => $company->accounts()->count(),
            'journal_entries' => $company->journalEntries()->count(),
            'journal_lines' => $company->journalLines()->count(),
            'opening_balance_migrations' => $company->openingBalanceMigrations()->count(),
            'year_end_closings' => $company->yearEndClosings()->count(),
            'originated_intercompany_transactions' => $company->originatedIntercompanyTransactions()->count(),
            'counterparty_intercompany_transactions' => $company->counterpartyIntercompanyTransactions()->count(),
        ];
        $payload = ['company_id' => $company->getKey(), 'counts' => $counts, 'posted_journals' => $journals];

        return [...$payload, 'checksum' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR))];
    }

    /** @param array<string, mixed> $expected
     * @return array{matches:bool,expected_checksum:string,actual_checksum:string}
     */
    public function verify(Company $company, array $expected): array
    {
        $actual = $this->generate($company);

        return [
            'matches' => isset($expected['checksum']) && hash_equals((string) $expected['checksum'], $actual['checksum']),
            'expected_checksum' => (string) ($expected['checksum'] ?? ''),
            'actual_checksum' => $actual['checksum'],
        ];
    }
}
