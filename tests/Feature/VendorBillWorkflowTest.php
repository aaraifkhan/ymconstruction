<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\AccountsPayable\ApproveVendorBillAction;
use App\Actions\AccountsPayable\PostVendorBillAction;
use App\Actions\AccountsPayable\ReverseVendorBillAction;
use App\Actions\AccountsPayable\ReviewVendorBillMatchAction;
use App\Actions\AccountsPayable\SubmitVendorBillAction;
use App\Actions\Inventory\HandoverGoodsReceiptToAccountsAction;
use App\Actions\Inventory\InspectGoodsReceiptAction;
use App\Actions\Inventory\ReceiveGoodsAction;
use App\Enums\AccountingMappingKey;
use App\Enums\AccountingProfile;
use App\Enums\JournalStatus;
use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Enums\TaxCalculationMethod;
use App\Enums\TaxCodeType;
use App\Enums\VendorBillDeductionType;
use App\Enums\VendorBillMatchStatus;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\AccountingMapping;
use App\Models\ApMatchingSetting;
use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\JournalLine;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\TaxCode;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\VendorBillDeduction;
use App\Models\VendorBillLine;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorBillWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_three_way_match_posts_grni_to_vendor_ap_once(): void
    {
        [$company, $receiptLine, $orderLine, $vendor, $project, $site, $actors] = $this->foundation();
        $bill = $this->bill($company, $orderLine->purchaseOrder, $vendor, $project, $site, $actors[3], '90.0000', '1200.0000');

        app(SubmitVendorBillAction::class)->handle($bill, $actors[3]);
        $this->assertSame('VB-2026-000001', $bill->refresh()->vendor_bill_number);
        $this->assertSame('90', (string) $bill->lines->first()->allocations()->sum('quantity'));
        app(ReviewVendorBillMatchAction::class)->handle($bill, $actors[4]);
        $this->assertSame(VendorBillMatchStatus::Matched, $bill->refresh()->match_status);
        app(ApproveVendorBillAction::class)->handle($bill, $actors[5]);
        app(PostVendorBillAction::class)->handle($bill, $actors[6]);
        app(PostVendorBillAction::class)->handle($bill, $actors[6]);

        $bill->refresh();
        $this->assertSame(VendorBillStatus::Posted, $bill->status);
        $this->assertSame(JournalStatus::Posted, $bill->journalEntry->status);
        $this->assertSame('108000.0000', $bill->journalEntry->debit_total);
        $this->assertSame($bill->journalEntry->debit_total, $bill->journalEntry->credit_total);
        $this->assertSame(1, VendorBill::query()->whereNotNull('journal_entry_id')->count());

        $grni = $this->mappingAccountId($company, AccountingMappingKey::Grni);
        $ap = $this->mappingAccountId($company, AccountingMappingKey::AccountsPayable);
        $this->assertSame('108000', (string) JournalLine::query()->where('journal_entry_id', $bill->journal_entry_id)
            ->where('account_id', $grni)->sum('debit'));
        $this->assertSame('108000', (string) JournalLine::query()->where('journal_entry_id', $bill->journal_entry_id)
            ->where('account_id', $ap)->sum('credit'));
        $this->assertSame('0', (string) JournalLine::query()->where('account_id', $grni)
            ->selectRaw('SUM(credit - debit) as balance')->value('balance'));
        $this->assertSame('0.0000', $receiptLine->refresh()->availableToInvoice());
    }

    public function test_bill_cannot_exceed_handed_over_accepted_quantity(): void
    {
        [$company, , $orderLine, $vendor, $project, $site, $actors] = $this->foundation();
        $bill = $this->bill($company, $orderLine->purchaseOrder, $vendor, $project, $site, $actors[3], '91.0000', '1200.0000');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeds handed-over accepted quantity');
        app(SubmitVendorBillAction::class)->handle($bill, $actors[3]);
    }

    public function test_configured_rate_tolerance_and_authorized_exception_are_snapshotted(): void
    {
        [$company, , $orderLine, $vendor, $project, $site, $actors] = $this->foundation();
        ApMatchingSetting::query()->where('company_id', $company->getKey())
            ->update(['rate_tolerance_percentage' => '10.0000']);
        $varianceAccount = $company->accounts()->where('code', '6900')->firstOrFail();
        $bill = $this->bill($company, $orderLine->purchaseOrder, $vendor, $project, $site, $actors[3], '80.0000', '1260.0000');
        $bill->lines()->update(['variance_account_id' => $varianceAccount->getKey()]);
        app(SubmitVendorBillAction::class)->handle($bill, $actors[3]);
        app(ReviewVendorBillMatchAction::class)->handle($bill, $actors[4]);
        $this->assertSame(VendorBillMatchStatus::WithinTolerance, $bill->refresh()->match_status);
        $this->assertNotNull($bill->match_snapshot_hash);

        $secondBill = $this->bill($company, $orderLine->purchaseOrder, $vendor, $project, $site, $actors[3], '1.0000', '1500.0000', 'VIN-002');
        $secondBill->lines()->update(['variance_account_id' => $varianceAccount->getKey()]);
        app(SubmitVendorBillAction::class)->handle($secondBill, $actors[3]);
        try {
            app(ReviewVendorBillMatchAction::class)->handle($secondBill, $actors[4]);
            $this->fail('Out-of-tolerance rate should require an exception.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('rate differs', $exception->getMessage());
        }
        app(ReviewVendorBillMatchAction::class)->handle($secondBill, $actors[4], true, 'Urgent approved price change.');
        $this->assertSame(VendorBillMatchStatus::ExceptionApproved, $secondBill->refresh()->match_status);
        $this->assertSame($actors[4]->getKey(), $secondBill->mismatch_overridden_by_id);
    }

    public function test_effective_tax_wht_retention_and_ap_posting_are_balanced(): void
    {
        [$company, , $orderLine, $vendor, $project, $site, $actors] = $this->foundation('10.0000', '100.0000');
        $salesTax = TaxCode::factory()->create([
            'company_id' => $company,
            'code' => 'GST-SYN-10',
            'type' => TaxCodeType::SalesTax,
            'rate' => '10.0000',
            'calculation_method' => TaxCalculationMethod::Exclusive,
            'effective_from' => '2026-07-01',
            'is_recoverable' => true,
            'is_active' => true,
        ]);
        $wht = TaxCode::factory()->create([
            'company_id' => $company,
            'code' => 'WHT-SYN-5',
            'type' => TaxCodeType::WithholdingTax,
            'rate' => '5.0000',
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);
        $bill = $this->bill($company, $orderLine->purchaseOrder, $vendor, $project, $site, $actors[3], '10.0000', '100.0000');
        $bill->lines()->update(['tax_code_id' => $salesTax->getKey()]);
        VendorBillDeduction::factory()->create([
            'vendor_bill_id' => $bill,
            'company_id' => $company,
            'type' => VendorBillDeductionType::WithholdingTax,
            'tax_code_id' => $wht,
            'description' => 'Synthetic WHT',
            'amount' => '1.0000',
        ]);
        VendorBillDeduction::factory()->create([
            'vendor_bill_id' => $bill,
            'company_id' => $company,
            'type' => VendorBillDeductionType::Retention,
            'description' => 'Contract retention',
            'amount' => '100.0000',
        ]);

        app(SubmitVendorBillAction::class)->handle($bill, $actors[3]);
        app(ReviewVendorBillMatchAction::class)->handle($bill, $actors[4], true, 'Synthetic tax configuration differs from the source PO.');
        app(ApproveVendorBillAction::class)->handle($bill, $actors[5]);
        app(PostVendorBillAction::class)->handle($bill, $actors[6]);

        $bill->refresh();
        $this->assertSame('1000.0000', $bill->subtotal);
        $this->assertSame('100.0000', $bill->tax_total);
        $this->assertSame('150.0000', $bill->deduction_total);
        $this->assertSame('950.0000', $bill->net_payable);
        $this->assertSame('1100.0000', $bill->journalEntry->debit_total);
        $this->assertSame($bill->journalEntry->debit_total, $bill->journalEntry->credit_total);
    }

    public function test_reversal_is_linked_idempotent_and_releases_receipt_for_rebilling(): void
    {
        [$company, $receiptLine, $orderLine, $vendor, $project, $site, $actors] = $this->foundation();
        $bill = $this->bill($company, $orderLine->purchaseOrder, $vendor, $project, $site, $actors[3], '90.0000', '1200.0000');
        app(SubmitVendorBillAction::class)->handle($bill, $actors[3]);
        app(ReviewVendorBillMatchAction::class)->handle($bill, $actors[4]);
        app(ApproveVendorBillAction::class)->handle($bill, $actors[5]);
        app(PostVendorBillAction::class)->handle($bill, $actors[6]);

        app(ReverseVendorBillAction::class)->handle($bill, $actors[6], CarbonImmutable::parse('2026-07-20'), 'Vendor invoice replaced.');
        $firstReversalId = $bill->refresh()->reversal_journal_entry_id;
        app(ReverseVendorBillAction::class)->handle($bill, $actors[6], CarbonImmutable::parse('2026-07-20'), 'Repeated request.');

        $this->assertSame(VendorBillStatus::Reversed, $bill->refresh()->status);
        $this->assertSame($firstReversalId, $bill->reversal_journal_entry_id);
        $this->assertSame('90.0000', $receiptLine->refresh()->availableToInvoice());
    }

    public function test_vendor_credit_note_posts_opposite_ap_and_source_account_directions(): void
    {
        [$company, , $orderLine, $vendor, $project, $site, $actors] = $this->foundation();
        $invoice = $this->bill($company, $orderLine->purchaseOrder, $vendor, $project, $site, $actors[3], '90.0000', '1200.0000');
        app(SubmitVendorBillAction::class)->handle($invoice, $actors[3]);
        app(ReviewVendorBillMatchAction::class)->handle($invoice, $actors[4]);
        app(ApproveVendorBillAction::class)->handle($invoice, $actors[5]);
        app(PostVendorBillAction::class)->handle($invoice, $actors[6]);

        $credit = VendorBill::factory()->create([
            'company_id' => $company,
            'purchase_order_id' => $orderLine->purchaseOrder,
            'original_vendor_bill_id' => $invoice,
            'vendor_id' => $vendor,
            'project_id' => $project,
            'project_site_id' => $site,
            'vendor_invoice_number' => 'VCN-SOURCE-001',
            'type' => VendorBillType::CreditNote,
            'invoice_date' => '2026-07-18',
            'due_date' => '2026-07-18',
            'prepared_by_id' => $actors[3],
        ]);
        VendorBillLine::factory()->create([
            'vendor_bill_id' => $credit,
            'company_id' => $company,
            'purchase_order_line_id' => $orderLine,
            'original_vendor_bill_line_id' => $invoice->lines()->firstOrFail(),
            'item_id' => $orderLine->item_id,
            'unit_of_measure_id' => $orderLine->unit_of_measure_id,
            'project_id' => $project,
            'project_site_id' => $site,
            'quantity' => '10.0000',
            'unit_rate' => '1200.0000',
        ]);

        app(SubmitVendorBillAction::class)->handle($credit, $actors[3]);
        app(ReviewVendorBillMatchAction::class)->handle($credit, $actors[4]);
        app(ApproveVendorBillAction::class)->handle($credit, $actors[5]);
        app(PostVendorBillAction::class)->handle($credit, $actors[6]);

        $credit->refresh();
        $ap = $this->mappingAccountId($company, AccountingMappingKey::AccountsPayable);
        $grni = $this->mappingAccountId($company, AccountingMappingKey::Grni);
        $this->assertSame('VCN-2026-000001', $credit->vendor_bill_number);
        $this->assertSame('12000', (string) $credit->journalEntry->lines()->where('account_id', $ap)->sum('debit'));
        $this->assertSame('12000', (string) $credit->journalEntry->lines()->where('account_id', $grni)->sum('credit'));
    }

    /**
     * @return array{Company, GoodsReceiptLine, PurchaseOrderLine, Party, Project, ProjectSite, array<int, User>}
     */
    private function foundation(string $acceptedQuantity = '90.0000', string $unitRate = '1200.0000'): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        $project = Project::factory()->create(['company_id' => $company]);
        $site = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $vendor = Party::factory()->forCompany($company)->withRoles(PartyRole::Vendor)->create();
        $item = Item::factory()->create(['company_id' => $company]);
        $actors = User::factory()->count(7)->create()->all();
        $role = Role::findOrCreate('super_admin');
        foreach ($actors as $actor) {
            $actor->assignRole($role);
        }
        $order = PurchaseOrder::factory()->create([
            'company_id' => $company,
            'purchase_requisition_id' => null,
            'vendor_id' => $vendor,
            'project_id' => $project,
            'project_site_id' => $site,
        ]);
        $orderLine = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'quantity' => $acceptedQuantity,
            'unit_rate' => $unitRate,
        ]);
        $order->update([
            'status' => PurchaseOrderStatus::Ordered,
            'ordered_by_id' => $actors[0]->getKey(),
            'ordered_at' => now(),
        ]);
        $receipt = GoodsReceipt::factory()->create([
            'purchase_order_id' => $order,
            'company_id' => $company,
            'vendor_id' => $vendor,
            'project_id' => $project,
            'project_site_id' => $site,
            'delivery_date' => '2026-07-15',
        ]);
        $receiptLine = GoodsReceiptLine::factory()->create([
            'goods_receipt_id' => $receipt,
            'company_id' => $company,
            'purchase_order_line_id' => $orderLine,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'received_quantity' => $acceptedQuantity,
        ]);
        app(ReceiveGoodsAction::class)->handle($receipt, $actors[0]);
        app(InspectGoodsReceiptAction::class)->handle($receipt, $actors[1], [
            $receiptLine->getKey() => [
                'accepted_quantity' => $acceptedQuantity,
                'rejected_quantity' => '0.0000',
            ],
        ]);
        app(HandoverGoodsReceiptToAccountsAction::class)->handle($receipt, $actors[2]);

        return [$company, $receiptLine, $orderLine, $vendor, $project, $site, $actors];
    }

    private function bill(
        Company $company,
        PurchaseOrder $order,
        Party $vendor,
        Project $project,
        ProjectSite $site,
        User $maker,
        string $quantity,
        string $rate,
        string $invoiceNumber = 'VIN-001',
    ): VendorBill {
        $bill = VendorBill::factory()->create([
            'company_id' => $company,
            'purchase_order_id' => $order,
            'vendor_id' => $vendor,
            'project_id' => $project,
            'project_site_id' => $site,
            'vendor_invoice_number' => $invoiceNumber,
            'invoice_date' => '2026-07-15',
            'due_date' => '2026-08-14',
            'prepared_by_id' => $maker,
        ]);
        $orderLine = $order->lines()->firstOrFail();
        VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill,
            'company_id' => $company,
            'purchase_order_line_id' => $orderLine,
            'item_id' => $orderLine->item_id,
            'unit_of_measure_id' => $orderLine->unit_of_measure_id,
            'project_id' => $project,
            'project_site_id' => $site,
            'quantity' => $quantity,
            'unit_rate' => $rate,
        ]);

        return $bill;
    }

    private function mappingAccountId(Company $company, AccountingMappingKey $key): int
    {
        return (int) AccountingMapping::query()
            ->whereBelongsTo($company)
            ->where('system_key', $key)
            ->value('account_id');
    }
}
