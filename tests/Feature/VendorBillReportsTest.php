<?php

namespace Tests\Feature;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\AccountingMappingKey;
use App\Enums\AccountingProfile;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InspectionResult;
use App\Enums\JournalStatus;
use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryCounterpartyType;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryTransactionType;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Enums\VoucherType;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\TreasuryAllocation;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Models\VendorBill;
use App\Reports\AccountsPayableAgingReport;
use App\Reports\UnmatchedReceiptReport;
use App\Reports\UnpaidVendorBillReport;
use App\Reports\VendorLedgerReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorBillReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_ledger_reconciles_posted_ap_control_lines(): void
    {
        [$company, $vendor, $maker, $poster] = $this->accountingFoundation();
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();
        $ap = (int) AccountingMapping::query()->whereBelongsTo($company)
            ->where('system_key', AccountingMappingKey::AccountsPayable)->value('account_id');
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $journal = JournalEntry::query()->create([
            'company_id' => $company->getKey(),
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => VoucherType::OpeningBalance,
            'idempotency_key' => Str::uuid(),
            'transaction_date' => '2026-07-15',
            'description' => 'Vendor ledger test',
            'prepared_by_id' => $maker->getKey(),
        ]);
        $journal->lines()->create([
            'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $cash->getKey(),
            'debit' => '100.0000', 'credit' => '0.0000',
        ]);
        $journal->lines()->create([
            'company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $ap,
            'debit' => '0.0000', 'credit' => '100.0000', 'party_id' => $vendor->getKey(),
        ]);
        $journal->update([
            'status' => JournalStatus::Approved,
            'submitted_by_id' => $maker->getKey(),
            'submitted_at' => now(),
            'approved_by_id' => $poster->getKey(),
            'approved_at' => now(),
        ]);
        app(PostJournalEntryAction::class)->handle($journal, $poster);

        $report = app(VendorLedgerReport::class)->forVendor(
            $company,
            $vendor,
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-31'),
        );
        $this->assertSame('100.0000', $report['credit_total']);
        $this->assertSame('100.0000', $report['closing_balance']);
        $this->assertCount(1, $report['lines']);
    }

    public function test_aging_unpaid_and_unmatched_receipt_reports_are_company_scoped(): void
    {
        [$company, $vendor, $maker, $poster] = $this->accountingFoundation();
        $project = Project::factory()->create(['company_id' => $company]);
        $site = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $item = Item::factory()->create(['company_id' => $company]);
        $order = PurchaseOrder::factory()->create([
            'company_id' => $company, 'purchase_requisition_id' => null, 'vendor_id' => $vendor,
            'project_id' => $project, 'project_site_id' => $site,
        ]);
        $orderLine = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order, 'company_id' => $company, 'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id, 'quantity' => '5.0000', 'unit_rate' => '100.0000',
        ]);
        $order->update(['status' => PurchaseOrderStatus::Ordered, 'ordered_by_id' => $maker->getKey(), 'ordered_at' => now()]);
        $receipt = GoodsReceipt::factory()->create([
            'company_id' => $company, 'purchase_order_id' => $order, 'vendor_id' => $vendor,
            'project_id' => $project, 'project_site_id' => $site, 'status' => GoodsReceiptStatus::HandedOver,
            'goods_receipt_number' => 'GRN-2026-REPORT', 'received_by_id' => $maker,
            'received_at' => now(), 'inspected_by_id' => $maker, 'inspected_at' => now(),
            'handed_over_by_id' => $maker, 'handed_over_at' => now(), 'accepted_value' => '500.0000',
        ]);
        GoodsReceiptLine::factory()->create([
            'goods_receipt_id' => $receipt, 'company_id' => $company, 'purchase_order_line_id' => $orderLine,
            'item_id' => $item, 'unit_of_measure_id' => $item->unit_of_measure_id,
            'received_quantity' => '5.0000', 'accepted_quantity' => '5.0000',
            'inspection_result' => InspectionResult::Accepted, 'unit_cost_snapshot' => '100.0000',
            'accepted_value' => '500.0000',
        ]);
        $bill = VendorBill::factory()->create([
            'company_id' => $company, 'purchase_order_id' => $order, 'vendor_id' => $vendor,
            'project_id' => $project, 'project_site_id' => $site, 'vendor_bill_number' => 'VB-2026-REPORT',
            'vendor_invoice_number' => 'REPORT-INV', 'type' => VendorBillType::Invoice,
            'status' => VendorBillStatus::Posted, 'invoice_date' => '2026-05-01',
            'due_date' => '2026-05-31', 'net_payable' => '500.0000', 'gross_total' => '500.0000',
            'prepared_by_id' => $maker,
        ]);

        $aging = app(AccountsPayableAgingReport::class)->forCompany($company, CarbonImmutable::parse('2026-07-15'));
        $this->assertSame('500.0000', $aging['buckets']['31_60']);
        $this->assertSame('500.0000', app(UnpaidVendorBillReport::class)->forCompany($company)->first()['open_amount']);
        $unmatched = app(UnmatchedReceiptReport::class)->forCompany($company)->first();
        $this->assertSame('5.0000', $unmatched['unmatched_quantity']);
        $this->assertSame('500.0000', $unmatched['unmatched_value']);

        $paymentPoster = User::factory()->create();
        $paymentPoster->assignRole(Role::findOrCreate('super_admin'));
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $payment = TreasuryTransaction::factory()->create([
            'company_id' => $company,
            'party_id' => $vendor,
            'source_account_id' => $cash,
            'type' => TreasuryTransactionType::Payment,
            'purpose' => TreasuryPurpose::Settlement,
            'counterparty_type' => TreasuryCounterpartyType::Party,
            'transaction_date' => '2026-07-15',
            'amount' => '500.0000',
            'description' => 'Full vendor settlement',
            'prepared_by_id' => $maker,
        ]);
        TreasuryAllocation::factory()->create([
            'treasury_transaction_id' => $payment,
            'company_id' => $company,
            'allocatable_type' => VendorBill::class,
            'allocatable_id' => $bill,
            'allocation_type' => TreasuryAllocationType::VendorBill,
            'amount' => '500.0000',
        ]);
        app(SubmitTreasuryTransactionAction::class)->handle($payment, $maker);
        app(ApproveTreasuryTransactionAction::class)->handle($payment, $poster);
        app(PostTreasuryTransactionAction::class)->handle($payment, $paymentPoster);

        $this->assertSame('0.0000', $bill->refresh()->postedOpenAmount());
        $this->assertTrue(app(UnpaidVendorBillReport::class)->forCompany($company)->isEmpty());
        $this->assertSame(
            '0.0000',
            app(AccountsPayableAgingReport::class)
                ->forCompany($company, CarbonImmutable::parse('2026-07-15'))['buckets']['31_60'],
        );
    }

    /** @return array{Company, Party, User, User} */
    private function accountingFoundation(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        $vendor = Party::factory()->forCompany($company)->withRoles(PartyRole::Vendor)->create();
        [$maker, $poster] = User::factory()->count(2)->create();
        $role = Role::findOrCreate('super_admin');
        $maker->assignRole($role);
        $poster->assignRole($role);

        return [$company, $vendor, $maker, $poster];
    }
}
