<?php

namespace Tests\Feature;

use App\Actions\Accounting\ApproveJournalEntryAction;
use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\SubmitJournalEntryAction;
use App\Actions\AccountsReceivable\ApproveCustomerInvoiceAction;
use App\Actions\AccountsReceivable\PostCustomerInvoiceAction;
use App\Actions\AccountsReceivable\SubmitCustomerInvoiceAction;
use App\Actions\Projects\ApproveProjectBudgetAction;
use App\Enums\AccountingProfile;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\ItemType;
use App\Models\Company;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceLine;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectBudgetLine;
use App\Models\User;
use App\Reports\AccountsReceivableAgingReport;
use App\Reports\CustomerLedgerReport;
use App\Reports\ProjectBudgetVsActualReport;
use App\Reports\ProjectProfitabilityReport;
use App\Reports\UnpaidCustomerInvoiceReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ar_aging_unpaid_customer_ledger_and_project_reports_use_posted_company_lines(): void
    {
        [$company, $customer, $project, $actors] = $this->foundation();
        $invoice = $this->postProjectInvoice($company, $customer, $project, $actors, '10000.0000');
        $this->postProjectCost($company, $project, $actors, '3000.0000');
        $budget = ProjectBudget::factory()->create([
            'company_id' => $company,
            'project_id' => $project,
            'prepared_by_id' => $actors[0],
        ]);
        ProjectBudgetLine::factory()->create([
            'project_budget_id' => $budget,
            'company_id' => $company,
            'amount' => '12000.0000',
        ]);
        app(ApproveProjectBudgetAction::class)->handle($budget, $actors[1]);

        $aging = app(AccountsReceivableAgingReport::class)->forCompany(
            $company,
            CarbonImmutable::parse('2026-08-31'),
        );
        $unpaid = app(UnpaidCustomerInvoiceReport::class)->forCompany($company);
        $ledger = app(CustomerLedgerReport::class)->forCustomer(
            $company,
            $customer,
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-31'),
        );
        $profit = app(ProjectProfitabilityReport::class)->forProject(
            $company,
            $project,
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-31'),
        );
        $budgetActual = app(ProjectBudgetVsActualReport::class)->forProject(
            $company,
            $project,
            CarbonImmutable::parse('2026-08-31'),
        );

        $this->assertSame('10000.0000', $aging['buckets']['1_30']);
        $this->assertCount(1, $unpaid);
        $this->assertSame($invoice->getKey(), $unpaid->first()['invoice']->getKey());
        $this->assertSame('10000.0000', $ledger['closing_balance']);
        $this->assertSame('10000.0000', $profit['revenue']);
        $this->assertSame('3000.0000', $profit['direct_cost']);
        $this->assertSame('7000.0000', $profit['gross_profit']);
        $this->assertSame('12000.0000', $budgetActual['budget']);
        $this->assertSame('3000.0000', $budgetActual['actual']);
        $this->assertSame('9000.0000', $budgetActual['variance']);

        $otherCompany = Company::factory()->create();
        $this->assertSame('10000.0000', array_reduce(
            app(AccountsReceivableAgingReport::class)->forCompany($company, CarbonImmutable::parse('2026-08-31'))['buckets'],
            fn (string $total, string $amount): string => bcadd($total, $amount, 4),
            '0.0000',
        ));
        $this->assertCount(0, app(UnpaidCustomerInvoiceReport::class)->forCompany($otherCompany));
    }

    /** @return array{Company, Party, Project, array<int, User>} */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle(
            $company,
            AccountingProfile::Construction,
            CarbonImmutable::parse('2026-07-15'),
        );
        $customer = Party::factory()->forCompany($company)->create();
        $project = Project::factory()->create([
            'company_id' => $company,
            'client_party_id' => $customer,
        ]);
        $actors = User::factory()->count(3)->create()->all();
        $role = Role::findOrCreate('super_admin');
        foreach ($actors as $actor) {
            $actor->assignRole($role);
        }

        return [$company, $customer, $project, $actors];
    }

    /** @param array<int, User> $actors */
    private function postProjectInvoice(
        Company $company,
        Party $customer,
        Project $project,
        array $actors,
        string $amount,
    ): CustomerInvoice {
        $item = Item::factory()->create([
            'company_id' => $company,
            'type' => ItemType::Service,
            'track_inventory' => false,
        ]);
        $invoice = CustomerInvoice::factory()->create([
            'company_id' => $company,
            'customer_id' => $customer,
            'project_id' => $project,
            'category' => CustomerInvoiceCategory::ServiceInvoice,
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
            'revenue_account_id' => $company->accounts()->where('code', '4100')->firstOrFail(),
            'quantity' => '1.0000',
            'unit_rate' => $amount,
        ]);
        app(SubmitCustomerInvoiceAction::class)->handle($invoice, $actors[0]);
        app(ApproveCustomerInvoiceAction::class)->handle($invoice, $actors[1]);
        app(PostCustomerInvoiceAction::class)->handle($invoice, $actors[2]);

        return $invoice->fresh();
    }

    /** @param array<int, User> $actors */
    private function postProjectCost(Company $company, Project $project, array $actors, string $amount): void
    {
        $period = $company->financialPeriods()->whereDate('starts_on', '<=', '2026-07-15')
            ->whereDate('ends_on', '>=', '2026-07-15')->firstOrFail();
        $journal = JournalEntry::factory()->forPeriod($company, $period, $actors[0])->create([
            'transaction_date' => '2026-07-15',
            'description' => 'Synthetic posted direct Project cost',
        ]);
        JournalLine::factory()->forEntryAndAccount(
            $journal,
            $company->accounts()->where('code', '7100')->firstOrFail(),
        )->create([
            'line_number' => 1,
            'debit' => $amount,
            'credit' => '0.0000',
            'project_id' => $project,
        ]);
        JournalLine::factory()->forEntryAndAccount(
            $journal,
            $company->accounts()->where('code', '1111')->firstOrFail(),
        )->create([
            'line_number' => 2,
            'debit' => '0.0000',
            'credit' => $amount,
        ]);
        app(SubmitJournalEntryAction::class)->handle($journal, $actors[0]);
        app(ApproveJournalEntryAction::class)->handle($journal, $actors[1]);
        app(PostJournalEntryAction::class)->handle($journal, $actors[2]);
    }
}
