<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Inventory\PostInventoryTransactionAction;
use App\Enums\AccountingProfile;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InspectionResult;
use App\Enums\InventoryTransactionStatus;
use App\Enums\InventoryTransactionType;
use App\Enums\JournalStatus;
use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryTransactionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_preserves_value_and_creates_no_gl_entry(): void
    {
        [$company, $project, $source, $destination, $item, $maker, $poster] = $this->foundation();
        InventoryBalance::factory()->create([
            'company_id' => $company,
            'project_site_id' => $source,
            'item_id' => $item,
            'quantity_on_hand' => '100.0000',
            'inventory_value' => '1000.0000',
            'average_unit_cost' => '10.0000',
        ]);
        $transaction = InventoryTransaction::factory()->create([
            'company_id' => $company,
            'type' => InventoryTransactionType::Transfer,
            'source_site_id' => $source,
            'destination_site_id' => $destination,
            'project_id' => null,
            'prepared_by_id' => $maker,
            'transaction_date' => '2026-07-15',
        ]);
        InventoryTransactionLine::factory()->create([
            'inventory_transaction_id' => $transaction,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'offset_account_id' => null,
            'quantity' => '40.0000',
        ]);

        app(PostInventoryTransactionAction::class)->handle($transaction, $poster);

        $sourceBalance = InventoryBalance::query()->where('project_site_id', $source->getKey())->firstOrFail();
        $destinationBalance = InventoryBalance::query()->where('project_site_id', $destination->getKey())->firstOrFail();
        $this->assertSame(InventoryTransactionStatus::Posted, $transaction->refresh()->status);
        $this->assertSame('INV-2026-000001', $transaction->transaction_number);
        $this->assertNull($transaction->journal_entry_id);
        $this->assertSame('60.0000', $sourceBalance->quantity_on_hand);
        $this->assertSame('600.0000', $sourceBalance->inventory_value);
        $this->assertSame('40.0000', $destinationBalance->quantity_on_hand);
        $this->assertSame('400.0000', $destinationBalance->inventory_value);
        $this->assertSame(2, InventoryMovement::query()->count());
    }

    public function test_project_issue_uses_weighted_average_and_posts_balanced_cost_journal(): void
    {
        [$company, $project, $source, , $item, $maker, $poster] = $this->foundation();
        InventoryBalance::factory()->create([
            'company_id' => $company,
            'project_site_id' => $source,
            'item_id' => $item,
            'quantity_on_hand' => '100.0000',
            'inventory_value' => '120000.0000',
            'average_unit_cost' => '1200.0000',
        ]);
        $directCost = $company->accounts()->where('code', '7100')->firstOrFail();
        $transaction = InventoryTransaction::factory()->create([
            'company_id' => $company,
            'type' => InventoryTransactionType::ProjectIssue,
            'source_site_id' => $source,
            'destination_site_id' => null,
            'project_id' => $project,
            'prepared_by_id' => $maker,
            'transaction_date' => '2026-07-15',
        ]);
        InventoryTransactionLine::factory()->create([
            'inventory_transaction_id' => $transaction,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'offset_account_id' => $directCost,
            'quantity' => '60.0000',
        ]);

        app(PostInventoryTransactionAction::class)->handle($transaction, $poster);

        $transaction->refresh();
        $balance = InventoryBalance::query()->where('project_site_id', $source->getKey())->firstOrFail();
        $this->assertSame('40.0000', $balance->quantity_on_hand);
        $this->assertSame('48000.0000', $balance->inventory_value);
        $this->assertSame('72000.0000', $transaction->total_value);
        $this->assertSame(JournalStatus::Posted, $transaction->journalEntry->status);
        $this->assertSame('72000.0000', $transaction->journalEntry->debit_total);
        $this->assertSame($transaction->journalEntry->debit_total, $transaction->journalEntry->credit_total);
        $this->assertSame(
            $project->getKey(),
            $transaction->journalEntry->lines()->where('account_id', $directCost->getKey())->value('project_id'),
        );
    }

    public function test_moving_weighted_average_recalculates_on_inbound_adjustment(): void
    {
        [$company, , $source, , $item, $maker, $poster] = $this->foundation();
        InventoryBalance::factory()->create([
            'company_id' => $company,
            'project_site_id' => $source,
            'item_id' => $item,
            'quantity_on_hand' => '10.0000',
            'inventory_value' => '100.0000',
            'average_unit_cost' => '10.0000',
        ]);
        $expense = $company->accounts()->where('code', '6900')->firstOrFail();
        $transaction = InventoryTransaction::factory()->create([
            'company_id' => $company,
            'type' => InventoryTransactionType::AdjustmentIncrease,
            'source_site_id' => null,
            'destination_site_id' => $source,
            'prepared_by_id' => $maker,
            'transaction_date' => '2026-07-15',
        ]);
        InventoryTransactionLine::factory()->create([
            'inventory_transaction_id' => $transaction,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'offset_account_id' => $expense,
            'quantity' => '10.0000',
            'unit_cost_snapshot' => '20.0000',
        ]);

        app(PostInventoryTransactionAction::class)->handle($transaction, $poster);

        $balance = InventoryBalance::query()->where('project_site_id', $source->getKey())->firstOrFail();
        $this->assertSame('20.0000', $balance->quantity_on_hand);
        $this->assertSame('300.0000', $balance->inventory_value);
        $this->assertSame('15.0000', $balance->average_unit_cost);
    }

    public function test_negative_stock_and_self_post_are_blocked_without_partial_movements(): void
    {
        [$company, $project, $source, , $item, $maker] = $this->foundation();
        $directCost = $company->accounts()->where('code', '7100')->firstOrFail();
        $transaction = InventoryTransaction::factory()->create([
            'company_id' => $company,
            'type' => InventoryTransactionType::ProjectIssue,
            'source_site_id' => $source,
            'destination_site_id' => null,
            'project_id' => $project,
            'prepared_by_id' => $maker,
            'transaction_date' => '2026-07-15',
        ]);
        InventoryTransactionLine::factory()->create([
            'inventory_transaction_id' => $transaction,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'offset_account_id' => $directCost,
            'quantity' => '1.0000',
        ]);

        try {
            app(PostInventoryTransactionAction::class)->handle($transaction, $maker);
            $this->fail('Self-post should have failed.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('preparer cannot post', mb_strtolower($exception->getMessage()));
        }

        $poster = User::factory()->create();
        $poster->assignRole(Role::findOrCreate('super_admin'));
        try {
            app(PostInventoryTransactionAction::class)->handle($transaction, $poster);
            $this->fail('Negative stock should have failed.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('negative inventory', $exception->getMessage());
        }

        $this->assertSame(0, InventoryMovement::query()->count());
        $this->assertSame(InventoryTransactionStatus::Draft, $transaction->refresh()->status);
    }

    public function test_vendor_return_reduces_stock_reopens_po_quantity_and_reverses_grni(): void
    {
        [$company, $project, $source, , $item, $maker, $poster] = $this->foundation();
        $vendor = Party::factory()->forCompany($company)->withRoles(PartyRole::Vendor)->create();
        $order = PurchaseOrder::factory()->create([
            'company_id' => $company,
            'purchase_requisition_id' => null,
            'vendor_id' => $vendor,
            'project_id' => $project,
            'project_site_id' => $source,
        ]);
        $orderLine = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'quantity' => '10.0000',
            'unit_rate' => '100.0000',
            'received_quantity' => '10.0000',
        ]);
        $order->update(['status' => PurchaseOrderStatus::Received]);
        $receipt = GoodsReceipt::factory()->create([
            'company_id' => $company,
            'purchase_order_id' => $order,
            'vendor_id' => $vendor,
            'project_id' => $project,
            'project_site_id' => $source,
            'goods_receipt_number' => 'GRN-2026-000001',
            'status' => GoodsReceiptStatus::HandedOver,
            'received_by_id' => User::factory(),
            'received_at' => now(),
            'inspected_by_id' => User::factory(),
            'inspected_at' => now(),
            'handed_over_by_id' => User::factory(),
            'handed_over_at' => now(),
            'accepted_value' => '1000.0000',
        ]);
        $receiptLine = GoodsReceiptLine::factory()->create([
            'goods_receipt_id' => $receipt,
            'company_id' => $company,
            'purchase_order_line_id' => $orderLine,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'received_quantity' => '10.0000',
            'accepted_quantity' => '10.0000',
            'rejected_quantity' => '0.0000',
            'unit_cost_snapshot' => '100.0000',
            'accepted_value' => '1000.0000',
            'inspection_result' => InspectionResult::Accepted,
        ]);
        InventoryBalance::factory()->create([
            'company_id' => $company,
            'project_site_id' => $source,
            'item_id' => $item,
            'quantity_on_hand' => '10.0000',
            'inventory_value' => '1000.0000',
            'average_unit_cost' => '100.0000',
        ]);
        $transaction = InventoryTransaction::factory()->create([
            'company_id' => $company,
            'type' => InventoryTransactionType::VendorReturn,
            'source_site_id' => $source,
            'destination_site_id' => null,
            'project_id' => null,
            'goods_receipt_id' => $receipt,
            'prepared_by_id' => $maker,
            'transaction_date' => '2026-07-15',
        ]);
        InventoryTransactionLine::factory()->create([
            'inventory_transaction_id' => $transaction,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'goods_receipt_line_id' => $receiptLine,
            'offset_account_id' => null,
            'quantity' => '4.0000',
        ]);

        app(PostInventoryTransactionAction::class)->handle($transaction, $poster);

        $this->assertSame('6.0000', InventoryBalance::query()->firstOrFail()->quantity_on_hand);
        $this->assertSame('4.0000', $receiptLine->refresh()->accepted_returned_quantity);
        $this->assertSame('6.0000', $orderLine->refresh()->received_quantity);
        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $order->refresh()->status);
        $this->assertSame('400.0000', $transaction->refresh()->total_value);
        $this->assertSame(JournalStatus::Posted, $transaction->journalEntry->status);
        $this->assertSame('400.0000', $transaction->journalEntry->debit_total);
    }

    /**
     * @return array{Company, Project, ProjectSite, ProjectSite, Item, User, User}
     */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        $project = Project::factory()->create(['company_id' => $company]);
        $source = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $destination = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $item = Item::factory()->create(['company_id' => $company]);
        [$maker, $poster] = User::factory()->count(2)->create();
        $role = Role::findOrCreate('super_admin');
        $maker->assignRole($role);
        $poster->assignRole($role);

        return [$company, $project, $source, $destination, $item, $maker, $poster];
    }
}
