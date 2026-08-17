<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\JournalStatus;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\JournalLine;
use App\Models\PettyCashReconciliation;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformPettyCashReconciliationAction
{
    public function handle(
        Company $company,
        User $actor,
        CarbonInterface $date,
        string $physicalCountedCash,
        string $onAccountHeld = '0.0000',
        ?string $explanation = null
    ): PettyCashReconciliation {
        if (bccomp($physicalCountedCash, '0.0000', 4) < 0 || bccomp($onAccountHeld, '0.0000', 4) < 0) {
            throw ValidationException::withMessages(['physical_counted_cash' => 'Amounts cannot be negative.']);
        }

        return DB::transaction(function () use (
            $company,
            $actor,
            $date,
            $physicalCountedCash,
            $onAccountHeld,
            $explanation
        ): PettyCashReconciliation {
            $mapping = AccountingMapping::where('company_id', $company->getKey())
                ->where('system_key', AccountingMappingKey::SitePettyCash)
                ->where('is_active', true)
                ->firstOrFail();

            // Calculate expected balance up to date
            $lines = JournalLine::query()
                ->where('company_id', $company->getKey())
                ->where('account_id', $mapping->account_id)
                ->whereHas('journalEntry', fn ($query) => $query
                    ->whereIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
                    ->whereDate('transaction_date', '<=', $date));

            $debitSum = (string) (clone $lines)->sum('debit');
            $creditSum = (string) (clone $lines)->sum('credit');
            $systemExpected = bcsub($debitSum, $creditSum, 4);

            // Difference = Expected - On Account - Physical Cash
            // A positive difference indicates cash shortage; negative indicates surplus.
            $accountedFor = bcadd($onAccountHeld, $physicalCountedCash, 4);
            $difference = bcsub($systemExpected, $accountedFor, 4);

            $reconciliation = PettyCashReconciliation::updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'reconciliation_date' => $date->toDateString(),
                ],
                [
                    'system_expected_balance' => $systemExpected,
                    'on_account_held' => $onAccountHeld,
                    'physical_counted_cash' => $physicalCountedCash,
                    'difference' => $difference,
                    'explanation' => $explanation,
                    'status' => 'reconciled',
                    'reconciled_by_id' => $actor->getKey(),
                ]
            );

            activity('petty_cash')->causedBy($actor)->performedOn($reconciliation)->event('reconciliation')
                ->withProperties([
                    'company_id' => $company->getKey(),
                    'date' => $date->toDateString(),
                    'expected' => $systemExpected,
                    'physical' => $physicalCountedCash,
                    'on_account' => $onAccountHeld,
                    'difference' => $difference,
                ])
                ->log('performed petty cash physical reconciliation');

            return $reconciliation->refresh();
        });
    }
}
