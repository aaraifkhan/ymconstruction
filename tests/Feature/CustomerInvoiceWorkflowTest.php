<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\AccountsReceivable\ApproveCustomerInvoiceAction;
use App\Actions\AccountsReceivable\PostCustomerInvoiceAction;
use App\Actions\AccountsReceivable\SubmitCustomerInvoiceAction;
use App\Actions\Treasury\ApproveTreasuryTransactionAction;
use App\Actions\Treasury\PostTreasuryTransactionAction;
use App\Actions\Treasury\SubmitTreasuryTransactionAction;
use App\Enums\AccountingMappingKey;
use App\Enums\AccountingProfile;
use App\Enums\CustomerInvoiceAdjustmentType;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\ItemType;
use App\Enums\JournalStatus;
use App\Enums\TaxCodeType;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryCounterpartyType;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryTransactionType;
use App\Models\Company;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceAdjustment;
use App\Models\CustomerInvoiceLine;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\TaxCode;
use App\Models\TreasuryAllocation;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerInvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_invoice_posts_ar_revenue_once_and_customer_receipt_settles_it(): void
    {
        [$company, $customer, $actors] = $this->foundation(AccountingProfile::ItServices);
        $invoice = $this->serviceInvoice($company, $customer, $actors[0], '10000.0000');

        $this->postInvoice($invoice, $actors);
        app(PostCustomerInvoiceAction::class)->handle($invoice->fresh(), $actors[2]);

        $invoice->refresh();
        $ar = $this->mappingAccountId($company, AccountingMappingKey::AccountsReceivable);
        $this->assertSame(CustomerInvoiceStatus::Posted, $invoice->status);
        $this->assertSame('SI-2026-000001', $invoice->invoice_number);
        $this->assertSame(JournalStatus::Posted, $invoice->journalEntry->status);
        $this->assertSame('10000.0000', $invoice->journalEntry->debit_total);
        $this->assertSame('10000', (string) $invoice->journalEntry->lines()->where('account_id', $ar)->sum('debit'));
        $this->assertSame(1, $company->journalEntries()->where('source_type', CustomerInvoice::class)->count());

        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $receipt = TreasuryTransaction::factory()->create([
            'company_id' => $company,
            'party_id' => $customer,
            'counterparty_type' => TreasuryCounterpartyType::Party,
            'type' => TreasuryTransactionType::Receipt,
            'purpose' => TreasuryPurpose::Settlement,
            'destination_account_id' => $cash,
            'transaction_date' => '2026-07-20',
            'amount' => '4000.0000',
            'description' => 'Partial customer receipt',
            'prepared_by_id' => $actors[0],
        ]);
        TreasuryAllocation::factory()->create([
            'treasury_transaction_id' => $receipt,
            'company_id' => $company,
            'allocatable_type' => CustomerInvoice::class,
            'allocatable_id' => $invoice,
            'allocation_type' => TreasuryAllocationType::CustomerInvoice,
            'amount' => '4000.0000',
        ]);
        app(SubmitTreasuryTransactionAction::class)->handle($receipt, $actors[0]);
        app(ApproveTreasuryTransactionAction::class)->handle($receipt, $actors[1]);
        app(PostTreasuryTransactionAction::class)->handle($receipt, $actors[2]);

        $this->assertSame('6000.0000', $invoice->fresh()->postedOpenAmount());
        $this->assertSame('4000', (string) $receipt->fresh()->journalEntry->lines()
            ->where('account_id', $ar)->sum('credit'));
    }

    public function test_running_bill_calculates_certification_retention_wht_and_mobilization_recovery(): void
    {
        [$company, $customer, $actors] = $this->foundation(AccountingProfile::Construction);
        $project = Project::factory()->create([
            'company_id' => $company,
            'client_party_id' => $customer,
            'contract_value' => '10000.0000',
        ]);
        $invoice = CustomerInvoice::factory()->create([
            'company_id' => $company,
            'customer_id' => $customer,
            'project_id' => $project,
            'category' => CustomerInvoiceCategory::RunningBill,
            'invoice_date' => '2026-07-15',
            'due_date' => '2026-08-14',
            'certificate_number' => 'IPC-001',
            'certificate_date' => '2026-07-14',
            'work_value' => '7500.0000',
            'variation_amount' => '500.0000',
            'prepared_by_id' => $actors[0],
        ]);
        CustomerInvoiceLine::factory()->create([
            'customer_invoice_id' => $invoice,
            'company_id' => $company,
            'line_number' => 1,
            'item_name_snapshot' => 'Certified construction work',
            'revenue_account_id' => $company->accounts()->where('code', '4100')->firstOrFail(),
            'quantity' => '1.0000',
            'unit_rate' => '8000.0000',
        ]);
        $wht = TaxCode::factory()->create([
            'company_id' => $company,
            'code' => 'WHT-SYN-5',
            'type' => TaxCodeType::WithholdingTax,
            'rate' => '5.0000',
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);
        CustomerInvoiceAdjustment::factory()->create([
            'customer_invoice_id' => $invoice,
            'company_id' => $company,
            'type' => CustomerInvoiceAdjustmentType::Retention,
            'amount' => '800.0000',
        ]);
        CustomerInvoiceAdjustment::factory()->create([
            'customer_invoice_id' => $invoice,
            'company_id' => $company,
            'type' => CustomerInvoiceAdjustmentType::WithholdingTax,
            'tax_code_id' => $wht,
            'description' => 'Configured WHT',
            'amount' => '1.0000',
        ]);
        CustomerInvoiceAdjustment::factory()->create([
            'customer_invoice_id' => $invoice,
            'company_id' => $company,
            'type' => CustomerInvoiceAdjustmentType::MobilizationRecovery,
            'description' => 'Mobilization recovery',
            'amount' => '500.0000',
        ]);

        $this->postInvoice($invoice, $actors);

        $invoice->refresh();
        $this->assertSame('8000.0000', $invoice->subtotal);
        $this->assertSame('800.0000', $invoice->retention_amount);
        $this->assertSame('400.0000', $invoice->wht_amount);
        $this->assertSame('500.0000', $invoice->mobilization_recovery_amount);
        $this->assertSame('6300.0000', $invoice->receivable_amount);
        $this->assertSame('10000.0000', $invoice->contract_value_snapshot);
        $this->assertNotNull($invoice->commercial_snapshot_hash);
        $this->assertSame($invoice->journalEntry->debit_total, $invoice->journalEntry->credit_total);
    }

    public function test_customer_credit_note_reverses_revenue_and_ar_with_cumulative_quantity_control(): void
    {
        [$company, $customer, $actors] = $this->foundation(AccountingProfile::ItServices);
        $invoice = $this->serviceInvoice($company, $customer, $actors[0], '5000.0000');
        $this->postInvoice($invoice, $actors);
        $sourceLine = $invoice->fresh()->lines()->firstOrFail();
        $credit = CustomerInvoice::factory()->create([
            'company_id' => $company,
            'customer_id' => $customer,
            'original_customer_invoice_id' => $invoice,
            'type' => CustomerInvoiceType::CreditNote,
            'category' => CustomerInvoiceCategory::ServiceInvoice,
            'invoice_date' => '2026-07-18',
            'due_date' => '2026-07-18',
            'prepared_by_id' => $actors[0],
        ]);
        CustomerInvoiceLine::factory()->create([
            'customer_invoice_id' => $credit,
            'company_id' => $company,
            'original_customer_invoice_line_id' => $sourceLine,
            'line_number' => 1,
            'item_id' => $sourceLine->item_id,
            'unit_of_measure_id' => $sourceLine->unit_of_measure_id,
            'item_name_snapshot' => $sourceLine->item_name_snapshot,
            'revenue_account_id' => $sourceLine->revenue_account_id,
            'quantity' => '1.0000',
            'unit_rate' => '2000.0000',
        ]);

        $this->postInvoice($credit, $actors);

        $ar = $this->mappingAccountId($company, AccountingMappingKey::AccountsReceivable);
        $this->assertSame('SCN-2026-000001', $credit->fresh()->invoice_number);
        $this->assertSame('2000', (string) $credit->fresh()->journalEntry->lines()->where('account_id', $ar)->sum('credit'));
        $this->assertSame('3000.0000', $invoice->fresh()->postedOpenAmount());
    }

    /** @return array{Company, Party, array<int, User>} */
    private function foundation(AccountingProfile $profile): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            $profile,
            CarbonImmutable::parse('2026-07-15'),
        );
        $customer = Party::factory()->forCompany($company)->create();
        $actors = User::factory()->count(3)->create()->all();
        $role = Role::findOrCreate('super_admin');
        foreach ($actors as $actor) {
            $actor->assignRole($role);
        }

        return [$company, $customer, $actors];
    }

    private function serviceInvoice(Company $company, Party $customer, User $maker, string $amount): CustomerInvoice
    {
        $item = Item::factory()->create([
            'company_id' => $company,
            'type' => ItemType::Service,
            'track_inventory' => false,
        ]);
        $invoice = CustomerInvoice::factory()->create([
            'company_id' => $company,
            'customer_id' => $customer,
            'category' => CustomerInvoiceCategory::ServiceInvoice,
            'invoice_date' => '2026-07-15',
            'due_date' => '2026-08-14',
            'prepared_by_id' => $maker,
        ]);
        CustomerInvoiceLine::factory()->create([
            'customer_invoice_id' => $invoice,
            'company_id' => $company,
            'line_number' => 1,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'revenue_account_id' => $company->accounts()->where('code', '4200')->firstOrFail(),
            'quantity' => '1.0000',
            'unit_rate' => $amount,
        ]);

        return $invoice;
    }

    /** @param array<int, User> $actors */
    private function postInvoice(CustomerInvoice $invoice, array $actors): void
    {
        app(SubmitCustomerInvoiceAction::class)->handle($invoice, $actors[0]);
        app(ApproveCustomerInvoiceAction::class)->handle($invoice, $actors[1]);
        app(PostCustomerInvoiceAction::class)->handle($invoice, $actors[2]);
    }

    private function mappingAccountId(Company $company, AccountingMappingKey $key): int
    {
        return (int) $company->accountingMappings()->where('system_key', $key)->value('account_id');
    }
}
