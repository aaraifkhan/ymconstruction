<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\AccountsReceivable\ApproveCustomerInvoiceAction;
use App\Actions\AccountsReceivable\PostCustomerInvoiceAction;
use App\Actions\AccountsReceivable\ReverseCustomerInvoiceAction;
use App\Actions\AccountsReceivable\SubmitCustomerInvoiceAction;
use App\Enums\AccountingProfile;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Models\Company;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceLine;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TradingSaleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_trading_sale_posts_weighted_average_cogs_and_reversal_restores_stock(): void
    {
        [$company, $invoice, $item, $site, $actors] = $this->saleFoundation('10.0000');

        $this->postInvoice($invoice, $actors);

        $invoice->refresh();
        $line = $invoice->lines()->firstOrFail();
        $balance = InventoryBalance::query()->where('project_site_id', $site->getKey())->where('item_id', $item->getKey())->firstOrFail();
        $this->assertSame(CustomerInvoiceStatus::Posted, $invoice->status);
        $this->assertSame('TS-2026-000001', $invoice->invoice_number);
        $this->assertSame('6000.0000', $line->cogs_amount);
        $this->assertSame('4.0000', $balance->quantity_on_hand);
        $this->assertSame('4000.0000', $balance->inventory_value);
        $this->assertSame($invoice->journalEntry->debit_total, $invoice->journalEntry->credit_total);

        app(ReverseCustomerInvoiceAction::class)->handle(
            $invoice,
            $actors[2],
            CarbonImmutable::parse('2026-07-20'),
            'Customer sale cancelled.',
        );

        $this->assertSame(CustomerInvoiceStatus::Reversed, $invoice->fresh()->status);
        $this->assertSame('10.0000', $balance->fresh()->quantity_on_hand);
        $this->assertSame(2, InventoryMovement::query()->where('customer_invoice_line_id', $line->getKey())->count());
    }

    public function test_trading_sale_cannot_create_negative_inventory_or_partial_accounting(): void
    {
        [, $invoice, , , $actors] = $this->saleFoundation('5.0000');
        app(SubmitCustomerInvoiceAction::class)->handle($invoice, $actors[0]);
        app(ApproveCustomerInvoiceAction::class)->handle($invoice, $actors[1]);

        try {
            app(PostCustomerInvoiceAction::class)->handle($invoice, $actors[2]);
            $this->fail('Trading sale above stock should fail.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('negative inventory', $exception->getMessage());
        }

        $this->assertSame(CustomerInvoiceStatus::Approved, $invoice->fresh()->status);
        $this->assertNull($invoice->journal_entry_id);
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    /** @return array{Company, CustomerInvoice, Item, ProjectSite, array<int, User>} */
    private function saleFoundation(string $stockQuantity): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Trading,
            CarbonImmutable::parse('2026-07-15'),
        );
        $customer = Party::factory()->forCompany($company)->create();
        $project = Project::factory()->create([
            'company_id' => $company,
            'client_party_id' => $customer,
        ]);
        $site = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $item = Item::factory()->create(['company_id' => $company]);
        InventoryBalance::factory()->create([
            'company_id' => $company,
            'project_site_id' => $site,
            'item_id' => $item,
            'quantity_on_hand' => $stockQuantity,
            'inventory_value' => bcmul($stockQuantity, '1000.0000', 4),
            'average_unit_cost' => '1000.0000',
        ]);
        $actors = User::factory()->count(3)->create()->all();
        $role = Role::findOrCreate('super_admin');
        foreach ($actors as $actor) {
            $actor->assignRole($role);
        }
        $invoice = CustomerInvoice::factory()->create([
            'company_id' => $company,
            'customer_id' => $customer,
            'project_id' => $project,
            'project_site_id' => $site,
            'category' => CustomerInvoiceCategory::TradingSale,
            'invoice_date' => '2026-07-15',
            'due_date' => '2026-08-14',
            'prepared_by_id' => $actors[0],
        ]);
        CustomerInvoiceLine::factory()->create([
            'customer_invoice_id' => $invoice,
            'company_id' => $company,
            'line_number' => 1,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'revenue_account_id' => $company->accounts()->where('code', '4400')->firstOrFail(),
            'cogs_account_id' => $company->accounts()->where('code', '7300')->firstOrFail(),
            'inventory_site_id' => $site,
            'quantity' => '6.0000',
            'unit_rate' => '1500.0000',
        ]);

        return [$company, $invoice, $item, $site, $actors];
    }

    /** @param array<int, User> $actors */
    private function postInvoice(CustomerInvoice $invoice, array $actors): void
    {
        app(SubmitCustomerInvoiceAction::class)->handle($invoice, $actors[0]);
        app(ApproveCustomerInvoiceAction::class)->handle($invoice, $actors[1]);
        app(PostCustomerInvoiceAction::class)->handle($invoice, $actors[2]);
    }
}
