<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Inventory\HandoverGoodsReceiptToAccountsAction;
use App\Actions\Inventory\InspectGoodsReceiptAction;
use App\Actions\Inventory\ReceiveGoodsAction;
use App\Actions\Inventory\RecordRejectedGoodsReturnAction;
use App\Enums\AccountingProfile;
use App\Enums\GoodsReceiptStatus;
use App\Enums\JournalStatus;
use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
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

class GoodsReceiptWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receive_inspect_and_handover_posts_only_accepted_inventory_and_grni(): void
    {
        [$receipt, $receiptLine, $orderLine, $receiver, $inspector, $accountsActor] = $this->receiptFoundation();

        app(ReceiveGoodsAction::class)->handle($receipt, $receiver);
        $this->assertSame(GoodsReceiptStatus::Received, $receipt->refresh()->status);
        $this->assertSame('GRN-2026-000001', $receipt->goods_receipt_number);
        $this->assertSame('100.0000', $orderLine->refresh()->received_quantity);
        $this->assertSame(PurchaseOrderStatus::Received, $receipt->purchaseOrder->refresh()->status);

        app(InspectGoodsReceiptAction::class)->handle($receipt, $inspector, [
            $receiptLine->getKey() => [
                'accepted_quantity' => '90.0000',
                'rejected_quantity' => '10.0000',
                'rejection_reason' => 'Damaged bags',
            ],
        ], 'Inspection completed.');

        $this->assertSame(GoodsReceiptStatus::Inspected, $receipt->refresh()->status);
        $this->assertSame('108000.0000', $receipt->accepted_value);
        $this->assertSame(0, InventoryBalance::query()->count());

        app(HandoverGoodsReceiptToAccountsAction::class)->handle($receipt, $accountsActor);

        $receipt->refresh();
        $balance = InventoryBalance::query()->firstOrFail();
        $journal = $receipt->inventoryJournalEntry;
        $this->assertSame(GoodsReceiptStatus::HandedOver, $receipt->status);
        $this->assertSame('90.0000', $balance->quantity_on_hand);
        $this->assertSame('108000.0000', $balance->inventory_value);
        $this->assertSame('1200.0000', $balance->average_unit_cost);
        $this->assertSame(1, InventoryMovement::query()->count());
        $this->assertSame('90.0000', InventoryMovement::query()->value('quantity'));
        $this->assertSame(JournalStatus::Posted, $journal->status);
        $this->assertSame('108000.0000', $journal->debit_total);
        $this->assertSame('108000.0000', $journal->credit_total);
        $this->assertSame(2, $journal->lines()->count());
    }

    public function test_partial_receipts_lock_po_quantities_and_prevent_over_receipt(): void
    {
        [$firstReceipt, , $orderLine, $receiver] = $this->receiptFoundation('60.0000');
        app(ReceiveGoodsAction::class)->handle($firstReceipt, $receiver);
        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $firstReceipt->purchaseOrder->refresh()->status);

        $secondReceipt = GoodsReceipt::factory()->create([
            'purchase_order_id' => $firstReceipt->purchase_order_id,
            'company_id' => $firstReceipt->company_id,
            'vendor_id' => $firstReceipt->vendor_id,
            'project_id' => $firstReceipt->project_id,
            'project_site_id' => $firstReceipt->project_site_id,
        ]);
        GoodsReceiptLine::factory()->create([
            'goods_receipt_id' => $secondReceipt,
            'company_id' => $secondReceipt->company_id,
            'purchase_order_line_id' => $orderLine,
            'item_id' => $orderLine->item_id,
            'unit_of_measure_id' => $orderLine->unit_of_measure_id,
            'received_quantity' => '41.0000',
        ]);

        try {
            app(ReceiveGoodsAction::class)->handle($secondReceipt, $receiver);
            $this->fail('Over-receipt should have failed.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('available to receive', $exception->getMessage());
        }

        $this->assertSame('60.0000', $orderLine->refresh()->received_quantity);
        $this->assertSame(GoodsReceiptStatus::Draft, $secondReceipt->refresh()->status);
    }

    public function test_receiver_cannot_inspect_and_rejected_return_is_bounded(): void
    {
        [$receipt, $line, , $receiver, $inspector] = $this->receiptFoundation();
        app(ReceiveGoodsAction::class)->handle($receipt, $receiver);

        try {
            app(InspectGoodsReceiptAction::class)->handle($receipt, $receiver, [
                $line->getKey() => [
                    'accepted_quantity' => '90.0000',
                    'rejected_quantity' => '10.0000',
                    'rejection_reason' => 'Damaged',
                ],
            ]);
            $this->fail('Receiver should not inspect the same receipt.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('receiver cannot inspect', mb_strtolower($exception->getMessage()));
        }

        app(InspectGoodsReceiptAction::class)->handle($receipt, $inspector, [
            $line->getKey() => [
                'accepted_quantity' => '90.0000',
                'rejected_quantity' => '10.0000',
                'rejection_reason' => 'Damaged',
            ],
        ]);
        app(RecordRejectedGoodsReturnAction::class)->handle($line, '6.0000', $receiver);
        $this->assertSame('6.0000', $line->refresh()->rejected_returned_quantity);
        $this->assertSame('94.0000', $line->purchaseOrderLine->refresh()->received_quantity);
        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $receipt->purchaseOrder->refresh()->status);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeds rejected material');
        app(RecordRejectedGoodsReturnAction::class)->handle($line, '5.0000', $receiver);
    }

    /**
     * @return array{GoodsReceipt, GoodsReceiptLine, PurchaseOrderLine, User, User, User}
     */
    private function receiptFoundation(string $receivedQuantity = '100.0000'): array
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
        [$receiver, $inspector, $accountsActor] = User::factory()->count(3)->create();
        $role = Role::findOrCreate('super_admin');
        $receiver->assignRole($role);
        $inspector->assignRole($role);
        $accountsActor->assignRole($role);
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
            'quantity' => '100.0000',
            'unit_rate' => '1200.0000',
        ]);
        $order->update([
            'status' => PurchaseOrderStatus::Ordered,
            'ordered_by_id' => User::factory()->create()->getKey(),
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
            'received_quantity' => $receivedQuantity,
        ]);

        return [$receipt, $receiptLine, $orderLine, $receiver, $inspector, $accountsActor];
    }
}
