<?php

namespace Tests\Feature;

use App\Actions\Documents\CreateDocumentAction;
use App\Actions\Procurement\ApproveProcurementDocumentAction;
use App\Actions\Procurement\SubmitPurchaseRequisitionAction;
use App\Enums\DocumentClassification;
use App\Enums\PartyRole;
use App\Enums\PurchaseRequisitionStatus;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseRequisitions\Pages\CreatePurchaseRequisition;
use App\Filament\Resources\PurchaseRequisitions\Pages\ListPurchaseRequisitions;
use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProcurementAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_resources_and_workflow_policies_are_tenant_scoped(): void
    {
        [$currentCompany, $currentRequisition] = $this->draftRequisition();
        [, $otherRequisition] = $this->draftRequisition();
        $user = $currentRequisition->preparedBy;
        $this->grant($user, $currentCompany, [
            'ViewAny:PurchaseRequisition',
            'View:PurchaseRequisition',
            'Create:PurchaseRequisition',
            'Submit:PurchaseRequisition',
        ]);
        $this->useTenant($user, $currentCompany);

        Livewire::test(ListPurchaseRequisitions::class)
            ->assertCanSeeTableRecords([$currentRequisition])
            ->assertCanNotSeeTableRecords([$otherRequisition]);
        $sourceLine = $currentRequisition->lines()->firstOrFail();
        Livewire::test(CreatePurchaseRequisition::class)
            ->assertFormFieldExists('project_id')
            ->assertFormFieldExists('project_site_id')
            ->assertFormFieldExists('lines')
            ->fillForm([
                'project_id' => $currentRequisition->project_id,
                'project_site_id' => $currentRequisition->project_site_id,
                'required_date' => today()->addWeek()->toDateString(),
                'currency_code' => 'PKR',
                'reason' => 'Created through procurement UI.',
                'lines' => [[
                    'item_id' => $sourceLine->item_id,
                    'unit_of_measure_id' => $sourceLine->unit_of_measure_id,
                    'quantity' => '2.0000',
                    'estimated_rate' => '100.0000',
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Gate::allows('submit', $currentRequisition));
        $this->assertFalse(Gate::allows('submit', $otherRequisition));
        $this->assertSame(
            1,
            PurchaseRequisition::query()->where('reason', 'Created through procurement UI.')->firstOrFail()->lines()->count(),
        );
    }

    public function test_preparer_cannot_approve_own_submitted_requisition(): void
    {
        [$company, $requisition] = $this->draftRequisition();
        $preparer = $requisition->preparedBy;
        $this->grant($preparer, $company, [
            'Submit:PurchaseRequisition',
            'Approve:PurchaseRequisition',
        ]);
        $this->useTenant($preparer, $company);
        app(SubmitPurchaseRequisitionAction::class)->handle($requisition, $preparer);
        $requisition->refresh();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('preparer cannot approve');
        app(ApproveProcurementDocumentAction::class)->handle($requisition, $preparer);
    }

    public function test_purchase_order_form_creates_company_scoped_lines(): void
    {
        [$company, $requisition] = $this->draftRequisition();
        PurchaseRequisition::query()->whereKey($requisition)->update([
            'status' => PurchaseRequisitionStatus::Approved,
            'requisition_number' => 'PR-2026-000001',
        ]);
        $requisition->refresh();
        $sourceLine = $requisition->lines()->firstOrFail();
        $vendor = Party::factory()->forCompany($company)->withRoles(PartyRole::Vendor)->create();
        $user = User::factory()->create();
        $this->grant($user, $company, [
            'ViewAny:PurchaseOrder',
            'Create:PurchaseOrder',
        ]);
        $this->useTenant($user, $company);

        Livewire::test(CreatePurchaseOrder::class)
            ->assertFormFieldExists('vendor_id')
            ->assertFormFieldExists('lines')
            ->fillForm([
                'purchase_requisition_id' => $requisition->getKey(),
                'vendor_id' => $vendor->getKey(),
                'project_id' => $requisition->project_id,
                'project_site_id' => $requisition->project_site_id,
                'order_date' => today()->toDateString(),
                'currency_code' => 'PKR',
                'payment_terms_days' => 15,
                'notes' => 'Created through purchase-order UI.',
                'lines' => [[
                    'purchase_requisition_line_id' => $sourceLine->getKey(),
                    'item_id' => $sourceLine->item_id,
                    'unit_of_measure_id' => $sourceLine->unit_of_measure_id,
                    'quantity' => '2.0000',
                    'unit_rate' => '100.0000',
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = PurchaseOrder::query()->where('notes', 'Created through purchase-order UI.')->firstOrFail();
        $this->assertTrue($order->company->is($company));
        $this->assertSame(1, $order->lines()->count());
    }

    public function test_private_documents_link_to_same_company_procurement_only(): void
    {
        Storage::fake('local');
        [$company, $requisition] = $this->draftRequisition();
        [, $otherRequisition] = $this->draftRequisition();
        $user = $requisition->preparedBy;
        $this->grant($user, $company, ['Create:Document']);
        $this->useTenant($user, $company);
        $category = DocumentCategory::factory()->for($company)->create(['is_active' => true]);
        $path = "documents/{$company->getKey()}/incoming/quotation.pdf";
        Storage::disk('local')->put($path, "%PDF-1.4\nquotation");

        $document = app(CreateDocumentAction::class)->handle(
            $company,
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Vendor quotation',
                'classification' => DocumentClassification::Restricted->value,
                'document_scope' => 'purchase_requisition',
                'related_record_id' => $requisition->getKey(),
            ],
            $path,
            'quotation.pdf',
            $user,
        );

        $this->assertTrue($document->documentable->is($requisition));
        $this->assertSame($company->getKey(), $document->company_id);

        $otherPath = "documents/{$company->getKey()}/incoming/cross-company.pdf";
        Storage::disk('local')->put($otherPath, "%PDF-1.4\nquotation");
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('current company');
        app(CreateDocumentAction::class)->handle(
            $company,
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Invalid quotation',
                'classification' => DocumentClassification::Restricted->value,
                'document_scope' => 'purchase_requisition',
                'related_record_id' => $otherRequisition->getKey(),
            ],
            $otherPath,
            'cross-company.pdf',
            $user,
        );
    }

    /**
     * @return array{Company, PurchaseRequisition}
     */
    private function draftRequisition(): array
    {
        $company = Company::factory()->create();
        $project = Project::factory()->create(['company_id' => $company]);
        $site = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $preparer = User::factory()->create();
        $item = Item::factory()->create(['company_id' => $company]);
        $requisition = PurchaseRequisition::factory()->create([
            'company_id' => $company,
            'project_id' => $project,
            'project_site_id' => $site,
            'prepared_by_id' => $preparer,
        ]);
        PurchaseRequisitionLine::factory()->create([
            'purchase_requisition_id' => $requisition,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
        ]);

        return [$company, $requisition];
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
