<?php

namespace Tests\Feature;

use App\Actions\Documents\CreateDocumentAction;
use App\Actions\Inventory\ReceiveGoodsAction;
use App\Enums\DocumentClassification;
use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Filament\Resources\InventoryBalances\Pages\ListInventoryBalances;
use App\Filament\Resources\InventoryMovements\Pages\ListInventoryMovements;
use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryBalance;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_goods_receipt_resource_and_workflow_are_company_scoped(): void
    {
        [$company, $receipt] = $this->draftReceipt();
        [, $otherReceipt] = $this->draftReceipt();
        $user = User::factory()->create();
        $this->grant($user, $company, [
            'ViewAny:GoodsReceipt',
            'View:GoodsReceipt',
            'Create:GoodsReceipt',
            'Receive:GoodsReceipt',
        ]);
        $this->useTenant($user, $company);

        Livewire::test(ListGoodsReceipts::class)
            ->assertCanSeeTableRecords([$receipt])
            ->assertCanNotSeeTableRecords([$otherReceipt]);
        $this->assertTrue(Gate::allows('receive', $receipt));
        $this->assertFalse(Gate::allows('receive', $otherReceipt));
    }

    public function test_filament_creates_company_scoped_goods_receipt_lines(): void
    {
        [$company, $existingReceipt, $orderLine] = $this->draftReceipt();
        $user = User::factory()->create();
        $this->grant($user, $company, [
            'ViewAny:GoodsReceipt',
            'Create:GoodsReceipt',
        ]);
        $this->useTenant($user, $company);

        Livewire::test(CreateGoodsReceipt::class)
            ->assertFormFieldExists('purchase_order_id')
            ->assertFormFieldExists('lines')
            ->fillForm([
                'purchase_order_id' => $existingReceipt->purchase_order_id,
                'vendor_id' => $existingReceipt->vendor_id,
                'project_id' => $existingReceipt->project_id,
                'project_site_id' => $existingReceipt->project_site_id,
                'delivery_date' => today()->toDateString(),
                'delivery_reference' => 'UI-DELIVERY-1',
                'lines' => [[
                    'purchase_order_line_id' => $orderLine->getKey(),
                    'item_id' => $orderLine->item_id,
                    'unit_of_measure_id' => $orderLine->unit_of_measure_id,
                    'received_quantity' => '5.0000',
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = GoodsReceipt::query()->where('delivery_reference', 'UI-DELIVERY-1')->firstOrFail();
        $this->assertSame($company->getKey(), $created->company_id);
        $this->assertSame($company->getKey(), $created->lines()->firstOrFail()->company_id);
    }

    public function test_inventory_reports_are_read_only_and_private_documents_reject_cross_company_links(): void
    {
        Storage::fake('local');
        [$company, $receipt] = $this->draftReceipt();
        [, $otherReceipt] = $this->draftReceipt();
        $user = User::factory()->create();
        $this->grant($user, $company, [
            'ViewAny:InventoryBalance',
            'ViewAny:InventoryMovement',
            'Create:Document',
        ]);
        $this->useTenant($user, $company);

        Livewire::test(ListInventoryBalances::class)->assertSuccessful();
        Livewire::test(ListInventoryMovements::class)->assertSuccessful();
        $this->assertFalse(Gate::allows('create', InventoryBalance::class));

        $category = DocumentCategory::factory()->for($company)->create(['is_active' => true]);
        $path = "documents/{$company->getKey()}/incoming/delivery.pdf";
        Storage::disk('local')->put($path, "%PDF-1.4\ndelivery");
        $document = app(CreateDocumentAction::class)->handle(
            $company,
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Delivery challan',
                'classification' => DocumentClassification::Restricted->value,
                'document_scope' => 'goods_receipt',
                'related_record_id' => $receipt->getKey(),
            ],
            $path,
            'delivery.pdf',
            $user,
        );
        $this->assertTrue($document->documentable->is($receipt));

        $otherPath = "documents/{$company->getKey()}/incoming/other.pdf";
        Storage::disk('local')->put($otherPath, "%PDF-1.4\nother");
        $this->expectException(ValidationException::class);
        app(CreateDocumentAction::class)->handle(
            $company,
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Cross-company delivery',
                'classification' => DocumentClassification::Restricted->value,
                'document_scope' => 'goods_receipt',
                'related_record_id' => $otherReceipt->getKey(),
            ],
            $otherPath,
            'other.pdf',
            $user,
        );
    }

    public function test_receive_action_denies_user_without_workflow_permission(): void
    {
        [$company, $receipt] = $this->draftReceipt();
        $user = User::factory()->create();
        $this->grant($user, $company, ['View:GoodsReceipt']);
        $this->useTenant($user, $company);

        $this->expectException(AuthorizationException::class);
        app(ReceiveGoodsAction::class)->handle($receipt, $user);
    }

    /**
     * @return array{Company, GoodsReceipt, PurchaseOrderLine}
     */
    private function draftReceipt(): array
    {
        $company = Company::factory()->create();
        $project = Project::factory()->create(['company_id' => $company]);
        $site = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $vendor = Party::factory()->forCompany($company)->withRoles(PartyRole::Vendor)->create();
        $item = Item::factory()->create(['company_id' => $company]);
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
            'unit_rate' => '100.0000',
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
        ]);
        GoodsReceiptLine::factory()->create([
            'goods_receipt_id' => $receipt,
            'company_id' => $company,
            'purchase_order_line_id' => $orderLine,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'received_quantity' => '10.0000',
        ]);

        return [$company, $receipt, $orderLine];
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
