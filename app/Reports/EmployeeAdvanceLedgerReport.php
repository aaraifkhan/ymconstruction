<?php

namespace App\Reports;

use App\Enums\AccountingMappingKey;
use App\Enums\JournalStatus;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\JournalLine;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class EmployeeAdvanceLedgerReport
{
    /** @return Collection<int, JournalLine> */
    public function forCompany(Company $company): Collection
    {
        $accountId = AccountingMapping::query()->whereBelongsTo($company)
            ->where('system_key', AccountingMappingKey::EmployeeAdvances)
            ->where('is_active', true)->value('account_id');
        if ($accountId === null) {
            throw ValidationException::withMessages(['accounting_mapping' => 'Employee Advances mapping is required.']);
        }

        return JournalLine::query()->whereBelongsTo($company)
            ->where('account_id', $accountId)->whereNotNull('employment_id')
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', [JournalStatus::Posted, JournalStatus::Reversed]))
            ->with([
                'journalEntry:id,voucher_number,transaction_date,description,status',
                'employment.employee:id,first_name,last_name',
            ])->get()->sortBy([
                ['employment_id', 'asc'],
                [fn (JournalLine $line): string => $line->journalEntry->transaction_date->toDateString(), 'asc'],
            ])->values();
    }
}
