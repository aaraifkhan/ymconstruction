<?php

namespace Tests\Feature\Filament;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Documents\CreateDocumentAction;
use App\Enums\AccountingProfile;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\DocumentClassification;
use App\Enums\ItemType;
use App\Filament\Pages\SalesReports;
use App\Filament\Resources\CustomerInvoices\Pages\CreateCustomerInvoice;
use App\Filament\Resources\CustomerInvoices\Pages\ListCustomerInvoices;
use App\Models\Company;
use App\Models\CustomerInvoice;
use App\Models\DocumentCategory;
use App\Models\Item;
use App\Models\Party;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_customer_invoice_resource_workflow_and_reports_are_tenant_scoped(): void
    {
        [$company, $invoice] = $this->draftInvoice();
        [, $otherInvoice] = $this->draftInvoice();
        $user = User::factory()->create();
        $this->grant($user, $company, [
            'ViewAny:CustomerInvoice', 'View:CustomerInvoice', 'Create:CustomerInvoice',
            'Approve:CustomerInvoice', 'View:SalesReports',
        ]);
        $this->useTenant($user, $company);

        Livewire::test(ListCustomerInvoices::class)
            ->assertCanSeeTableRecords([$invoice])
            ->assertCanNotSeeTableRecords([$otherInvoice]);
        Livewire::test(SalesReports::class)->assertSuccessful();

        $invoice->update(['status' => CustomerInvoiceStatus::Submitted]);
        $this->assertTrue(Gate::allows('approve', $invoice->refresh()));
        $this->assertFalse(Gate::allows('post', $invoice));
        $this->assertFalse(Gate::allows('approve', $otherInvoice));
    }

    public function test_filament_creates_company_scoped_customer_invoice_lines(): void
    {
        [$company, $existingInvoice, $item] = $this->draftInvoice();
        $user = User::factory()->create();
        $this->grant($user, $company, ['ViewAny:CustomerInvoice', 'Create:CustomerInvoice']);
        $this->useTenant($user, $company);

        Livewire::test(CreateCustomerInvoice::class)
            ->assertFormFieldExists('customer_id')
            ->assertFormFieldExists('lines')
            ->fillForm([
                'type' => 'invoice',
                'category' => 'service_invoice',
                'customer_id' => $existingInvoice->customer_id,
                'invoice_date' => today()->toDateString(),
                'due_date' => today()->addDays(30)->toDateString(),
                'currency_code' => 'PKR',
                'description' => 'UI service invoice',
                'lines' => [[
                    'item_id' => $item->getKey(),
                    'unit_of_measure_id' => $item->unit_of_measure_id,
                    'item_name_snapshot' => $item->name,
                    'quantity' => '2.0000',
                    'unit_rate' => '500.0000',
                    'revenue_account_id' => $company->accounts()->where('code', '4200')->firstOrFail()->getKey(),
                ]],
            ])->call('create')->assertHasNoFormErrors();

        $created = CustomerInvoice::query()->where('description', 'UI service invoice')->firstOrFail();
        $this->assertSame($company->getKey(), $created->company_id);
        $this->assertSame($company->getKey(), $created->lines()->firstOrFail()->company_id);
    }

    public function test_private_sales_documents_reject_cross_company_customer_invoices(): void
    {
        Storage::fake('local');
        [$company, $invoice] = $this->draftInvoice();
        [, $otherInvoice] = $this->draftInvoice();
        $user = User::factory()->create();
        $this->grant($user, $company, ['Create:Document']);
        $this->useTenant($user, $company);
        $category = DocumentCategory::factory()->for($company)->create(['is_active' => true]);
        $path = "documents/{$company->getKey()}/incoming/customer-invoice.pdf";
        Storage::disk('local')->put($path, "%PDF-1.4\ninvoice");

        $document = app(CreateDocumentAction::class)->handle(
            $company,
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Customer invoice',
                'classification' => DocumentClassification::Restricted->value,
                'document_scope' => 'customer_invoice',
                'related_record_id' => $invoice->getKey(),
            ],
            $path,
            'customer-invoice.pdf',
            $user,
        );
        $this->assertTrue($document->documentable->is($invoice));

        $otherPath = "documents/{$company->getKey()}/incoming/cross-company.pdf";
        Storage::disk('local')->put($otherPath, "%PDF-1.4\ninvoice");
        $this->expectException(ValidationException::class);
        app(CreateDocumentAction::class)->handle(
            $company,
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Cross-company customer invoice',
                'classification' => DocumentClassification::Restricted->value,
                'document_scope' => 'customer_invoice',
                'related_record_id' => $otherInvoice->getKey(),
            ],
            $otherPath,
            'cross-company.pdf',
            $user,
        );
    }

    /** @return array{Company, CustomerInvoice, Item} */
    private function draftInvoice(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::ItServices,
            CarbonImmutable::parse('2026-07-15'),
        );
        $customer = Party::factory()->forCompany($company)->create();
        $item = Item::factory()->create([
            'company_id' => $company,
            'type' => ItemType::Service,
            'track_inventory' => false,
        ]);
        $invoice = CustomerInvoice::factory()->create([
            'company_id' => $company,
            'customer_id' => $customer,
            'category' => CustomerInvoiceCategory::ServiceInvoice,
            'prepared_by_id' => User::factory(),
        ]);

        return [$company, $invoice, $item];
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
