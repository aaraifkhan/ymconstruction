<?php

namespace App\Actions\Assets;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Enums\AssetAccountingStatus;
use App\Enums\AssetStatus;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AssetDisposal;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostAssetDisposalAction
{
    public function __construct(private PostJournalEntryAction $postJournalEntry) {}

    public function handle(AssetDisposal $disposal, User $actor): AssetDisposal
    {
        Gate::forUser($actor)->authorize('post', $disposal);

        return DB::transaction(function () use ($disposal, $actor): AssetDisposal {
            $disposal = AssetDisposal::query()->with(['fixedAsset.category'])->whereKey($disposal)->lockForUpdate()->firstOrFail();
            if ($disposal->status === AssetAccountingStatus::Posted) {
                return $disposal;
            }
            if ($disposal->status !== AssetAccountingStatus::Approved || (int) $disposal->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'Approved disposal requires an independent poster.']);
            }
            $asset = $disposal->fixedAsset;
            if ($asset->status !== AssetStatus::Active || bccomp($asset->carryingAmount(), (string) $disposal->carrying_amount, 4) !== 0) {
                throw ValidationException::withMessages(['fixed_asset_id' => 'Asset balance changed after disposal approval.']);
            }
            $category = $asset->category;
            if ((bccomp((string) $disposal->gain_amount, '0', 4) === 1 && $category->disposal_gain_account_id === null)
                || (bccomp((string) $disposal->loss_amount, '0', 4) === 1 && $category->disposal_loss_account_id === null)
                || (bccomp((string) $disposal->proceeds_amount, '0', 4) === 1 && $disposal->proceeds_account_id === null)) {
                throw ValidationException::withMessages(['accounts' => 'Disposal proceeds and gain/loss mappings are required.']);
            }
            $period = FinancialPeriod::query()->where('company_id', $disposal->company_id)->where('status', FinancialPeriodStatus::Open)->whereDate('starts_on', '<=', $disposal->disposal_date)->whereDate('ends_on', '>=', $disposal->disposal_date)->lockForUpdate()->first();
            if ($period === null) {
                throw ValidationException::withMessages(['disposal_date' => 'Disposal date requires an open period.']);
            }
            $journal = JournalEntry::query()->firstOrCreate(
                ['company_id' => $disposal->company_id, 'idempotency_key' => "AssetDisposal:{$disposal->getKey()}:posting"],
                ['financial_year_id' => $period->financial_year_id, 'financial_period_id' => $period->getKey(), 'voucher_type' => VoucherType::Journal, 'status' => JournalStatus::Draft, 'transaction_date' => $disposal->disposal_date, 'reference' => $asset->asset_number, 'description' => "Disposal of {$asset->asset_number}", 'currency_code' => 'PKR', 'source_type' => $disposal::class, 'source_id' => $disposal->getKey(), 'prepared_by_id' => $disposal->prepared_by_id],
            );
            if ($journal->lines()->doesntExist()) {
                $n = 1;
                $dims = ['project_id' => $asset->project_id, 'project_site_id' => $asset->project_site_id, 'cost_center_id' => $asset->cost_center_id, 'fixed_asset_id' => $asset->getKey()];
                foreach ([[$disposal->proceeds_account_id, $disposal->proceeds_amount, 0], [$category->accumulated_depreciation_account_id, $disposal->accumulated_depreciation_amount, 0], [$category->disposal_loss_account_id, $disposal->loss_amount, 0], [$category->cost_account_id, 0, $disposal->cost_amount], [$category->disposal_gain_account_id, 0, $disposal->gain_amount]] as [$account, $debit, $credit]) {
                    if (bccomp((string) $debit, '0', 4) === 1 || bccomp((string) $credit, '0', 4) === 1) {
                        $journal->lines()->create(['company_id' => $asset->company_id, 'line_number' => $n++, 'account_id' => $account, 'description' => $asset->name, 'debit' => $debit, 'credit' => $credit, ...$dims]);
                    }
                }
                $journal->update(['status' => JournalStatus::Approved, 'submitted_by_id' => $disposal->prepared_by_id, 'submitted_at' => $disposal->created_at, 'approved_by_id' => $disposal->approved_by_id, 'approved_at' => $disposal->approved_at]);
            }
            $journal = $this->postJournalEntry->handle($journal, $actor);
            $asset->update(['status' => AssetStatus::Disposed]);
            $disposal->update(['status' => AssetAccountingStatus::Posted, 'posted_by_id' => $actor->getKey(), 'posted_at' => now(), 'journal_entry_id' => $journal->getKey()]);
            activity('asset_disposals')->causedBy($actor)->performedOn($disposal)->event('posted')->log('posted asset disposal');

            return $disposal->refresh();
        }, attempts: 3);
    }
}
