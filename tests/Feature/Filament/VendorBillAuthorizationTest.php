<?php

namespace Tests\Feature\Filament;

use App\Actions\Documents\CreateDocumentAction;
use App\Enums\DocumentClassification;
use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Enums\VendorBillStatus;
use App\Filament\Pages\AccountsPayableReports;
use App\Filament\Resources\VendorBills\Pages\CreateVendorBill;
use App\Filament\Resources\VendorBills\Pages\ListVendorBills;
use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Models\VendorBill;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VendorBillAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_vendor_bill_resource_and_match_permissions_are_tenant_scoped(): void
    {
        [$company, $bill] = $this->draftBill();
        [, $otherBill] = $this->draftBill();
        $user = User::factory()->create();
        $this->grant($user, $company, [
            'ViewAny:VendorBill', 'View:VendorBill', 'Create:VendorBill',
            'ReviewMatch:VendorBill', 'View:AccountsPayableReports',
        ]);
        $this->useTenant($user, $company);

        Livewire::test(ListVendorBills::class)
            ->assertCanSeeTableRecords([$bill])
            ->assertCanNotSeeTableRecords([$otherBill]);
        Livewire::test(AccountsPayableReports::class)->assertSuccessful();

        $bill->update(['status' => VendorBillStatus::Submitted]);
        $this->assertTrue(Gate::allows('reviewMatch', $bill->refresh()));
        $this->assertFalse(Gate::allows('overrideMatch', $bill));
        $this->assertFalse(Gate::allows('reviewMatch', $otherBill));
    }

    public function test_filament_creates_company_scoped_vendor_bill_lines(): void
    {
        [$company, $existingBill, $orderLine] = $this->draftBill();
        $user = User::factory()->create();
        $this->grant($user, $company, ['ViewAny:VendorBill', 'Create:VendorBill']);
        $this->useTenant($user, $company);

        Livewire::test(CreateVendorBill::class)
            ->assertFormFieldExists('purchase_order_id')
            ->assertFormFieldExists('lines')
            ->fillForm([
                'type' => 'invoice',
                'purchase_order_id' => $existingBill->purchase_order_id,
                'vendor_id' => $existingBill->vendor_id,
                'vendor_invoice_number' => 'UI-VIN-001',
                'invoice_date' => today()->toDateString(),
                'due_date' => today()->addDays(30)->toDateString(),
                'project_id' => $existingBill->project_id,
                'project_site_id' => $existingBill->project_site_id,
                'currency_code' => 'PKR',
                'lines' => [[
                    'purchase_order_line_id' => $orderLine->getKey(),
                    'item_id' => $orderLine->item_id,
                    'unit_of_measure_id' => $orderLine->unit_of_measure_id,
                    'item_name_snapshot' => $orderLine->item_name_snapshot,
                    'quantity' => '2.0000',
                    'unit_rate' => '100.0000',
                    'project_id' => $existingBill->project_id,
                    'project_site_id' => $existingBill->project_site_id,
                ]],
            ])->call('create')->assertHasNoFormErrors();

        $created = VendorBill::query()->where('vendor_invoice_number', 'UI-VIN-001')->firstOrFail();
        $this->assertSame($company->getKey(), $created->company_id);
        $this->assertSame($company->getKey(), $created->lines()->firstOrFail()->company_id);
    }

    public function test_private_invoice_documents_reject_cross_company_vendor_bills(): void
    {
        Storage::fake('local');
        [$company, $bill] = $this->draftBill();
        [, $otherBill] = $this->draftBill();
        $user = User::factory()->create();
        $this->grant($user, $company, ['Create:Document']);
        $this->useTenant($user, $company);
        $category = DocumentCategory::factory()->for($company)->create(['is_active' => true]);
        $path = "documents/{$company->getKey()}/incoming/vendor-invoice.pdf";
        Storage::disk('local')->put($path, "%PDF-1.4\ninvoice");

        $document = app(CreateDocumentAction::class)->handle(
            $company,
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Vendor invoice',
                'classification' => DocumentClassification::Restricted->value,
                'document_scope' => 'vendor_bill',
                'related_record_id' => $bill->getKey(),
            ],
            $path,
            'vendor-invoice.pdf',
            $user,
        );
        $this->assertTrue($document->documentable->is($bill));

        $otherPath = "documents/{$company->getKey()}/incoming/cross-company.pdf";
        Storage::disk('local')->put($otherPath, "%PDF-1.4\ninvoice");
        $this->expectException(ValidationException::class);
        app(CreateDocumentAction::class)->handle(
            $company,
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Cross-company vendor invoice',
                'classification' => DocumentClassification::Restricted->value,
                'document_scope' => 'vendor_bill',
                'related_record_id' => $otherBill->getKey(),
            ],
            $otherPath,
            'cross-company.pdf',
            $user,
        );
    }

    /** @return array{Company, VendorBill, PurchaseOrderLine} */
    private function draftBill(): array
    {
        $company = Company::factory()->create();
        $project = Project::factory()->create(['company_id' => $company]);
        $site = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $vendor = Party::factory()->forCompany($company)->withRoles(PartyRole::Vendor)->create();
        $item = Item::factory()->create(['company_id' => $company]);
        $order = PurchaseOrder::factory()->create([
            'company_id' => $company, 'purchase_requisition_id' => null, 'vendor_id' => $vendor,
            'project_id' => $project, 'project_site_id' => $site,
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order, 'company_id' => $company, 'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id, 'quantity' => '10.0000', 'unit_rate' => '100.0000',
        ]);
        $order->update([
            'status' => PurchaseOrderStatus::Ordered,
            'ordered_by_id' => User::factory()->create()->getKey(),
            'ordered_at' => now(),
        ]);
        $bill = VendorBill::factory()->create([
            'company_id' => $company, 'purchase_order_id' => $order, 'vendor_id' => $vendor,
            'project_id' => $project, 'project_site_id' => $site, 'prepared_by_id' => User::factory(),
        ]);

        return [$company, $bill, $line];
    }

    /** @param array<int, string> $permissions */
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
