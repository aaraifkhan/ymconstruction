<?php

namespace App\Actions\Assets;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Enums\AssetAcquisitionSource;
use App\Enums\AssetStatus;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\VendorBillStatus;
use App\Enums\VoucherType;
use App\Models\FinancialPeriod;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CapitalizeFixedAssetAction
{
    public function __construct(private PostJournalEntryAction $postJournalEntry) {}

    public function handle(FixedAsset $asset, User $actor): FixedAsset
    {
        Gate::forUser($actor)->authorize('capitalize', $asset);

        return DB::transaction(function () use ($asset, $actor): FixedAsset {
            $asset = FixedAsset::query()->with(['category', 'vendorBillLine.vendorBill'])->whereKey($asset)->lockForUpdate()->firstOrFail();
            if ($asset->status === AssetStatus::Active) {
                return $asset;
            }
            if ($asset->status !== AssetStatus::Approved || (int) $asset->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'An independently posted approved asset is required.']);
            }
            $journalId = null;
            if ($asset->acquisition_source === AssetAcquisitionSource::VendorBill) {
                $line = $asset->vendorBillLine;
                if ($line?->vendorBill?->status !== VendorBillStatus::Posted
                    || (int) $line->clearing_account_id !== (int) $asset->category->cost_account_id
                    || bccomp((string) $line->line_total, (string) $asset->acquisition_cost, 4) !== 0) {
                    throw ValidationException::withMessages(['vendor_bill_line_id' => 'Use a posted Vendor Bill line capitalized to the category cost account for the exact asset cost.']);
                }
            } else {
                if ($asset->capitalization_credit_account_id === null) {
                    throw ValidationException::withMessages(['capitalization_credit_account_id' => 'Manual capitalization requires a credit account.']);
                }
                $period = FinancialPeriod::query()->where('company_id', $asset->company_id)->where('status', FinancialPeriodStatus::Open)
                    ->whereDate('starts_on', '<=', $asset->acquired_on)->whereDate('ends_on', '>=', $asset->acquired_on)->lockForUpdate()->first();
                if ($period === null) {
                    throw ValidationException::withMessages(['acquired_on' => 'Acquisition date requires an open period.']);
                }
                $journal = JournalEntry::query()->firstOrCreate(
                    ['company_id' => $asset->company_id, 'idempotency_key' => "FixedAsset:{$asset->getKey()}:capitalization"],
                    [
                        'financial_year_id' => $period->financial_year_id, 'financial_period_id' => $period->getKey(),
                        'voucher_type' => VoucherType::Journal, 'status' => JournalStatus::Draft,
                        'transaction_date' => $asset->acquired_on, 'reference' => $asset->asset_number,
                        'description' => "Capitalization of {$asset->asset_number}", 'currency_code' => 'PKR',
                        'source_type' => $asset::class, 'source_id' => $asset->getKey(), 'prepared_by_id' => $asset->prepared_by_id,
                    ],
                );
                if ($journal->lines()->doesntExist()) {
                    $dimensions = ['project_id' => $asset->project_id, 'project_site_id' => $asset->project_site_id, 'cost_center_id' => $asset->cost_center_id, 'fixed_asset_id' => $asset->getKey()];
                    $journal->lines()->create(['company_id' => $asset->company_id, 'line_number' => 1, 'account_id' => $asset->category->cost_account_id, 'description' => $asset->name, 'debit' => $asset->acquisition_cost, 'credit' => 0, ...$dimensions]);
                    $journal->lines()->create(['company_id' => $asset->company_id, 'line_number' => 2, 'account_id' => $asset->capitalization_credit_account_id, 'description' => $asset->name, 'debit' => 0, 'credit' => $asset->acquisition_cost, 'fixed_asset_id' => $asset->getKey()]);
                    $journal->update(['status' => JournalStatus::Approved, 'submitted_by_id' => $asset->submitted_by_id, 'submitted_at' => $asset->submitted_at, 'approved_by_id' => $asset->approved_by_id, 'approved_at' => $asset->approved_at]);
                }
                $journalId = $this->postJournalEntry->handle($journal, $actor)->getKey();
            }
            $asset->update(['status' => AssetStatus::Active, 'capitalized_by_id' => $actor->getKey(), 'capitalized_at' => now(), 'acquisition_journal_entry_id' => $journalId]);
            activity('fixed_assets')->causedBy($actor)->performedOn($asset)->event('capitalized')->withProperties(['journal_entry_id' => $journalId])->log('capitalized fixed asset');

            return $asset->refresh();
        }, attempts: 3);
    }
}
