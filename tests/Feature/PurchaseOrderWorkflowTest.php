<?php

namespace Tests\Feature;

use App\Actions\Procurement\ApproveProcurementDocumentAction;
use App\Actions\Procurement\CancelPurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseOrderFromRequisitionAction;
use App\Actions\Procurement\IssuePurchaseOrderAction;
use App\Actions\Procurement\SubmitPurchaseOrderAction;
use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\Company;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\TaxCode;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchaseOrderWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_approved_snapshot_is_immutable_and_issue_reserves_requisition_quantity_without_gl(): void
    {
        [$requisition, $requisitionLine, $vendor] = $this->approvedRequisition('10.0000');
        $preparer = User::factory()->create();
        $this->grant($preparer, $requisition->company, [
            'Create:PurchaseOrder',
            'Submit:PurchaseOrder',
        ]);
        $this->useTenant($preparer, $requisition->company);

        $order = app(CreatePurchaseOrderFromRequisitionAction::class)->handle(
            $requisition,
            $vendor,
            [[
                'purchase_requisition_line_id' => $requisitionLine->getKey(),
                'quantity' => '6.0000',
                'unit_rate' => '125.0000',
            ]],
            today(),
            $preparer,
        );
        app(SubmitPurchaseOrderAction::class)->handle($order, $preparer);
        $order->refresh();

        $approver = User::factory()->create();
        $this->grant($approver, $requisition->company, ['Approve:PurchaseOrder']);
        $this->useTenant($approver, $requisition->company);
        app(ApproveProcurementDocumentAction::class)->handle($order, $approver);
        $order->refresh();

        $this->assertSame(PurchaseOrderStatus::Approved, $order->status);
        $this->assertSame('750.0000', $order->subtotal);
        $this->assertSame('750.0000', $order->grand_total);
        $this->assertNotNull($order->approved_snapshot);
        $this->assertSame(
            hash('sha256', json_encode($order->approved_snapshot, JSON_THROW_ON_ERROR)),
            $order->approved_snapshot_hash,
        );

        try {
            $order->update(['notes' => 'Silent commercial change']);
            $this->fail('Approved purchase-order header was mutable.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        try {
            $order->lines()->firstOrFail()->update(['quantity' => '7.0000']);
            $this->fail('Approved purchase-order line was mutable.');
        } catch (ValidationException) {
            // Continue with the controlled issue workflow.
        }

        $issuer = User::factory()->create();
        $this->grant($issuer, $requisition->company, ['Issue:PurchaseOrder']);
        $this->useTenant($issuer, $requisition->company);
        app(IssuePurchaseOrderAction::class)->handle($order, $issuer);

        $this->assertSame(PurchaseOrderStatus::Ordered, $order->refresh()->status);
        $this->assertSame('6.0000', $requisitionLine->refresh()->ordered_quantity);
        $this->assertSame(PurchaseRequisitionStatus::PartiallyOrdered, $requisition->refresh()->status);
        $this->assertSame(0, JournalEntry::query()->count());
    }

    public function test_competing_purchase_orders_cannot_over_order_same_requisition_line(): void
    {
        [$requisition, $line, $vendor] = $this->approvedRequisition('10.0000');
        $actor = User::factory()->create();
        $this->grant($actor, $requisition->company, [
            'Create:PurchaseOrder',
            'Submit:PurchaseOrder',
            'Approve:PurchaseOrder',
            'Issue:PurchaseOrder',
        ]);
        $this->useTenant($actor, $requisition->company);

        $first = $this->approvedOrder($requisition, $line, $vendor, $actor, '6.0000');
        $second = $this->approvedOrder($requisition, $line, $vendor, $actor, '6.0000');
        app(IssuePurchaseOrderAction::class)->handle($first, $actor);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('remaining requisition quantity');
        app(IssuePurchaseOrderAction::class)->handle($second, $actor);
    }

    public function test_cancelling_unreceived_issued_order_releases_requisition_commitment(): void
    {
        [$requisition, $line, $vendor] = $this->approvedRequisition('10.0000');
        $actor = User::factory()->create();
        $this->grant($actor, $requisition->company, [
            'Create:PurchaseOrder',
            'Submit:PurchaseOrder',
            'Approve:PurchaseOrder',
            'Issue:PurchaseOrder',
            'Cancel:PurchaseOrder',
        ]);
        $this->useTenant($actor, $requisition->company);

        $order = $this->approvedOrder($requisition, $line, $vendor, $actor, '4.0000');
        app(IssuePurchaseOrderAction::class)->handle($order, $actor);
        app(CancelPurchaseOrderAction::class)->handle($order, $actor, 'Vendor unable to supply.');

        $this->assertSame(PurchaseOrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame('0.0000', $line->refresh()->ordered_quantity);
        $this->assertSame(PurchaseRequisitionStatus::Approved, $requisition->refresh()->status);
    }

    public function test_purchase_order_rejects_cross_company_vendor_and_tax(): void
    {
        [$requisition, $line] = $this->approvedRequisition();
        $otherCompany = Company::factory()->create();
        $otherVendor = Party::factory()->forCompany($otherCompany)->withRoles(PartyRole::Vendor)->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('purchase-order company');
        PurchaseOrder::factory()->create([
            'company_id' => $requisition->company_id,
            'purchase_requisition_id' => $requisition,
            'vendor_id' => $otherVendor,
            'project_id' => $requisition->project_id,
            'project_site_id' => $requisition->project_site_id,
            'prepared_by_id' => User::factory(),
        ]);
    }

    public function test_purchase_order_line_rejects_cross_company_tax_code(): void
    {
        [$requisition, $requisitionLine, $vendor] = $this->approvedRequisition();
        $otherTaxCode = TaxCode::factory()->create(['company_id' => Company::factory()->create()]);
        $order = PurchaseOrder::factory()->create([
            'company_id' => $requisition->company_id,
            'purchase_requisition_id' => $requisition,
            'vendor_id' => $vendor,
            'project_id' => $requisition->project_id,
            'project_site_id' => $requisition->project_site_id,
            'prepared_by_id' => User::factory(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tax code must belong');
        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order,
            'company_id' => $order->company_id,
            'purchase_requisition_line_id' => $requisitionLine,
            'item_id' => $requisitionLine->item_id,
            'unit_of_measure_id' => $requisitionLine->unit_of_measure_id,
            'tax_code_id' => $otherTaxCode,
        ]);
    }

    public function test_order_with_received_quantity_cannot_be_cancelled(): void
    {
        [$requisition, $line, $vendor] = $this->approvedRequisition();
        $actor = User::factory()->create();
        $this->grant($actor, $requisition->company, [
            'Create:PurchaseOrder',
            'Submit:PurchaseOrder',
            'Issue:PurchaseOrder',
            'Cancel:PurchaseOrder',
        ]);
        $this->useTenant($actor, $requisition->company);
        $order = $this->approvedOrder($requisition, $line, $vendor, $actor, '4.0000');
        app(IssuePurchaseOrderAction::class)->handle($order, $actor);
        $order->refresh();
        $order->lines()->firstOrFail()->update(['received_quantity' => '1.0000']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('received quantity');
        app(CancelPurchaseOrderAction::class)->handle($order, $actor, 'Attempted cancellation.');
    }

    /**
     * @return array{PurchaseRequisition, PurchaseRequisitionLine, Party}
     */
    private function approvedRequisition(string $quantity = '10.0000'): array
    {
        $company = Company::factory()->create();
        $project = Project::factory()->create(['company_id' => $company]);
        $site = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $preparer = User::factory()->create();
        $item = Item::factory()->create(['company_id' => $company]);
        $vendor = Party::factory()->forCompany($company)->withRoles(PartyRole::Vendor)->create();
        $requisition = PurchaseRequisition::factory()->create([
            'company_id' => $company,
            'project_id' => $project,
            'project_site_id' => $site,
            'prepared_by_id' => $preparer,
        ]);
        $line = PurchaseRequisitionLine::factory()->create([
            'purchase_requisition_id' => $requisition,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'quantity' => $quantity,
            'estimated_rate' => '100.0000',
        ]);
        PurchaseRequisition::query()->whereKey($requisition)->update([
            'status' => PurchaseRequisitionStatus::Approved,
            'estimated_total' => bcmul($quantity, '100.0000', 4),
        ]);

        return [$requisition->refresh(), $line, $vendor];
    }

    private function approvedOrder(
        PurchaseRequisition $requisition,
        PurchaseRequisitionLine $line,
        Party $vendor,
        User $actor,
        string $quantity,
    ): PurchaseOrder {
        $order = app(CreatePurchaseOrderFromRequisitionAction::class)->handle(
            $requisition->refresh(),
            $vendor,
            [[
                'purchase_requisition_line_id' => $line->getKey(),
                'quantity' => $quantity,
                'unit_rate' => '100.0000',
            ]],
            today(),
            $actor,
        );
        app(SubmitPurchaseOrderAction::class)->handle($order, $actor);
        $order->refresh();
        $approver = User::factory()->create();
        $this->grant($approver, $requisition->company, ['Approve:PurchaseOrder']);
        $this->useTenant($approver, $requisition->company);
        app(ApproveProcurementDocumentAction::class)->handle($order, $approver);

        return $order->refresh();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function grant(User $user, Company $company, array $permissions): void
    {
        $user->companies()->syncWithoutDetaching([
            $company->getKey() => ['is_active' => true, 'can_access_descendants' => false],
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }

    private function useTenant(User $user, Company $company): void
    {
        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
