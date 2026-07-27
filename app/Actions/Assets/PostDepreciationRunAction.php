<?php

namespace App\Actions\Assets;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Enums\AssetAccountingStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\DepreciationRun;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostDepreciationRunAction
{
    public function __construct(private PostJournalEntryAction $postJournalEntry) {}

    public function handle(DepreciationRun $run, User $actor): DepreciationRun
    {
        Gate::forUser($actor)->authorize('post', $run);

        return DB::transaction(function () use ($run, $actor): DepreciationRun {
            $run = DepreciationRun::query()->with(['lines.fixedAsset', 'financialPeriod'])->whereKey($run)->lockForUpdate()->firstOrFail();
            if ($run->status === AssetAccountingStatus::Posted) {
                return $run;
            }
            if ($run->status !== AssetAccountingStatus::Approved || (int) $run->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'Only an independently posted approved run is eligible.']);
            }
            foreach ($run->lines as $line) {
                if (bccomp((string) $line->fixedAsset->accumulated_depreciation, (string) $line->opening_accumulated_depreciation, 4) !== 0) {
                    throw ValidationException::withMessages(['assets' => 'Asset balances changed after generation; regenerate the run.']);
                }
            }
            $journal = JournalEntry::query()->firstOrCreate(
                ['company_id' => $run->company_id, 'idempotency_key' => "DepreciationRun:{$run->getKey()}:posting"],
                [
                    'financial_year_id' => $run->financialPeriod->financial_year_id, 'financial_period_id' => $run->financial_period_id,
                    'voucher_type' => VoucherType::Depreciation, 'status' => JournalStatus::Draft, 'transaction_date' => $run->depreciation_date,
                    'reference' => $run->reference_number, 'description' => 'Fixed asset depreciation', 'currency_code' => 'PKR',
                    'source_type' => $run::class, 'source_id' => $run->getKey(), 'prepared_by_id' => $run->prepared_by_id,
                ],
            );
            if ($journal->lines()->doesntExist()) {
                $n = 1;
                foreach ($run->lines as $line) {
                    $dims = ['project_id' => $line->project_id, 'project_site_id' => $line->project_site_id, 'cost_center_id' => $line->cost_center_id, 'fixed_asset_id' => $line->fixed_asset_id];
                    $journal->lines()->create(['company_id' => $run->company_id, 'line_number' => $n++, 'account_id' => $line->expense_account_id, 'description' => $line->fixedAsset->name, 'debit' => $line->depreciation_amount, 'credit' => 0, ...$dims]);
                    $journal->lines()->create(['company_id' => $run->company_id, 'line_number' => $n++, 'account_id' => $line->accumulated_depreciation_account_id, 'description' => $line->fixedAsset->name, 'debit' => 0, 'credit' => $line->depreciation_amount, ...$dims]);
                }
                $journal->update(['status' => JournalStatus::Approved, 'submitted_by_id' => $run->submitted_by_id, 'submitted_at' => $run->submitted_at, 'approved_by_id' => $run->approved_by_id, 'approved_at' => $run->approved_at]);
            }
            $journal = $this->postJournalEntry->handle($journal, $actor);
            foreach ($run->lines as $line) {
                $line->fixedAsset->update(['accumulated_depreciation' => $line->closing_accumulated_depreciation]);
            }
            $run->update(['reference_number' => $journal->voucher_number, 'status' => AssetAccountingStatus::Posted, 'posted_by_id' => $actor->getKey(), 'posted_at' => now(), 'journal_entry_id' => $journal->getKey()]);
            activity('depreciation_runs')->causedBy($actor)->performedOn($run)->event('posted')->log('posted depreciation run');

            return $run->refresh();
        }, attempts: 3);
    }
}
