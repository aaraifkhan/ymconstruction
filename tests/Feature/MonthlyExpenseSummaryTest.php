<?php

namespace Tests\Feature;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\RecordQuickExpenseAction;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Filament\Pages\MonthlyExpenseSummaryPage;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\User;
use App\Reports\MonthlyExpenseSummaryReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonthlyExpenseSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_monthly_expense_summary_report_roll_up(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'BM Construction Pvt Ltd', 'slug' => 'bmc-construction']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $project = Project::factory()->for($company)->create(['name' => 'Pine City Executive']);

        $role = Role::findOrCreate('super_admin');
        $maker = User::factory()->create();
        $poster = User::factory()->create();
        $maker->assignRole($role);
        $poster->assignRole($role);

        $period = $company->financialPeriods()->whereDate('starts_on', '<=', '2026-08-01')->whereDate('ends_on', '>=', '2026-08-01')->firstOrFail();
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $pettyCash = $company->accounts()->where('code', '1112')->firstOrFail();
        $equity = $company->accounts()->where('allows_manual_posting', true)->where('code', 'LIKE', '3%')->firstOrFail();
        $opening = JournalEntry::query()->create([
            'company_id' => $company->getKey(),
            'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(),
            'voucher_type' => VoucherType::OpeningBalance,
            'idempotency_key' => Str::uuid(),
            'transaction_date' => '2026-08-01',
            'description' => 'Opening cash capital',
            'prepared_by_id' => $maker->getKey(),
        ]);
        $opening->lines()->create(['company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $cash->getKey(), 'debit' => '500000.0000', 'credit' => '0.0000']);
        $opening->lines()->create(['company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $pettyCash->getKey(), 'debit' => '50000.0000', 'credit' => '0.0000']);
        $opening->lines()->create(['company_id' => $company->getKey(), 'line_number' => 3, 'account_id' => $equity->getKey(), 'debit' => '0.0000', 'credit' => '550000.0000']);
        $opening->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($opening, $poster);

        $quickExpense = app(RecordQuickExpenseAction::class);

        // 1. HO Expense: Salaries PKR 200,000 via Cash
        $j1 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-05'),
            category: ExpenseCategory::Salaries,
            paymentMethod: ExpensePaymentMethod::Cash,
            amount: '200000.0000',
            description: 'Staff salaries Aug 2026',
        );
        $j1->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j1, $poster);

        // 2. Bidding Expense: Tender Documentation PKR 15,000 via Petty Cash
        $j2 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-06'),
            category: ExpenseCategory::TenderDocumentation,
            paymentMethod: ExpensePaymentMethod::PettyCash,
            amount: '15000.0000',
            description: 'Tender printing & binding documents',
        );
        $j2->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j2, $poster);

        // 3. Project Direct Cost: Sand PKR 80,000 via Cash
        $j3 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-07'),
            category: ExpenseCategory::Sand,
            paymentMethod: ExpensePaymentMethod::Cash,
            amount: '80000.0000',
            description: 'Sand dumpers for site',
            projectId: $project->getKey(),
        );
        $j3->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j3, $poster);

        // 4. Director Funded Expense: Office Rent PKR 90,000 via Director
        $j4 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-08'),
            category: ExpenseCategory::OfficeRent,
            paymentMethod: ExpensePaymentMethod::Director,
            amount: '90000.0000',
            description: 'Head office rent paid by Director',
        );
        $j4->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j4, $poster);

        // Execute Report
        $reportService = app(MonthlyExpenseSummaryReport::class);
        $report = $reportService->forCompany($company, CarbonImmutable::parse('2026-08-01'), CarbonImmutable::parse('2026-08-31'));

        $this->assertSame('290000.0000', $report['head_office_expenses']['total']);
        $this->assertSame('15000.0000', $report['bidding_expenses']['total']);
        $this->assertSame('80000.0000', $report['project_expenses']['total']);
        $this->assertSame('385000.0000', $report['grand_total_expenditure']);

        // Check funding source breakdowns
        $this->assertSame('280000.0000', $report['funding_sources']['cash']['total']); // 200k + 80k
        $this->assertSame('15000.0000', $report['funding_sources']['petty_cash']['total']);
        $this->assertSame('90000.0000', $report['funding_sources']['director']['total']);
    }

    public function test_monthly_expense_summary_page_renders_and_supports_presets(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(MonthlyExpenseSummaryPage::class)
            ->assertSuccessful()
            ->call('setLastMonth')
            ->assertSet('fromDate', today()->subMonth()->startOfMonth()->toDateString())
            ->call('setThisMonth')
            ->assertSet('fromDate', today()->startOfMonth()->toDateString())
            ->call('setThisFiscalYear');
    }
}
