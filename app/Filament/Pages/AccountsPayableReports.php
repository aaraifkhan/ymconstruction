<?php

namespace App\Filament\Pages;

use App\Reports\AccountsPayableAgingReport;
use App\Reports\UnmatchedReceiptReport;
use App\Reports\UnpaidVendorBillReport;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AccountsPayableReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Accounts Payable';

    protected string $view = 'filament.pages.accounts-payable-reports';

    /** @var array<string, string> */
    public array $agingBuckets = [];

    /** @var array<int, array<string, mixed>> */
    public array $unpaidBills = [];

    /** @var array<int, array<string, mixed>> */
    public array $unmatchedReceipts = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:AccountsPayableReports'));
    }

    public function mount(
        AccountsPayableAgingReport $aging,
        UnpaidVendorBillReport $unpaid,
        UnmatchedReceiptReport $unmatched,
    ): void {
        abort_unless(static::canAccess(), 403);
        $company = Filament::getTenant();
        $this->agingBuckets = $aging->forCompany($company, today())['buckets'];
        $this->unpaidBills = $unpaid->forCompany($company)->map(fn (array $row): array => [
            'number' => $row['bill']->vendor_bill_number,
            'vendor' => $row['bill']->vendor->name,
            'due_date' => $row['bill']->due_date->toDateString(),
            'open_amount' => $row['open_amount'],
        ])->all();
        $this->unmatchedReceipts = $unmatched->forCompany($company)->map(fn (array $row): array => [
            'grn' => $row['receipt_line']->goodsReceipt->goods_receipt_number,
            'item' => $row['receipt_line']->item_name_snapshot,
            'quantity' => $row['unmatched_quantity'],
            'value' => $row['unmatched_value'],
        ])->all();
    }
}
