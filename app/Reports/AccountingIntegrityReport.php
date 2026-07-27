<?php

namespace App\Reports;

use App\Enums\JournalStatus;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Collection;

class AccountingIntegrityReport
{
    /** @return array<string, mixed> */
    public function forCompany(Company $company): array
    {
        $unbalanced = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.company_id', $company->getKey())
            ->whereIn('journal_entries.status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
            ->groupBy('journal_entries.id')
            ->havingRaw('ROUND(SUM(journal_lines.debit) - SUM(journal_lines.credit), 4) <> 0')
            ->pluck('journal_entries.id');
        $orphanCompanyLines = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.company_id', $company->getKey())
            ->whereColumn('journal_lines.company_id', '<>', 'journal_entries.company_id')
            ->pluck('journal_lines.id');
        $missingPostedEvidence = JournalEntry::query()->where('company_id', $company->getKey())
            ->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed])
            ->where(fn ($query) => $query->whereNull('posted_by_id')->orWhereNull('posted_at')->orWhereNull('voucher_number'))
            ->pluck('id');

        return [
            'unbalanced_journal_ids' => $unbalanced,
            'cross_tenant_line_ids' => $orphanCompanyLines,
            'missing_posting_evidence_ids' => $missingPostedEvidence,
            'passes' => Collection::make([$unbalanced, $orphanCompanyLines, $missingPostedEvidence])->every->isEmpty(),
        ];
    }
}
