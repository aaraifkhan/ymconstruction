<?php

namespace Tests\Feature;

use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\RecordQuickExpenseAction;
use App\Actions\Accounting\TransferBiddingCostToProjectAction;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Filament\Pages\BiddingExpenseLedgerPage;
use App\Filament\Pages\DirectorExpenseLedgerPage;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\User;
use App\Reports\BiddingExpenseLedgerReport;
use App\Reports\DirectorExpenseLedgerReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DirectorAndBiddingExpenseViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_director_expense_ledger_calculates_funding_reimbursements_and_net_due(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'BM Construction Pvt Ltd', 'slug' => 'bmc-construction']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $maker = User::factory()->create();
        $poster = User::factory()->create();
        $maker->assignRole($role);
        $poster->assignRole($role);

        $quickExpense = app(RecordQuickExpenseAction::class);

        // 1. Director funds Office Rent: PKR 120,000
        $j1 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-05'),
            category: ExpenseCategory::OfficeRent,
            paymentMethod: ExpensePaymentMethod::Director,
            amount: '120000.0000',
            description: 'HO Office Rent Aug 2026',
        );
        $j1->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j1, $poster);

        // 2. Director funds Fuel: PKR 15,000
        $j2 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-08'),
            category: ExpenseCategory::Fuel,
            paymentMethod: ExpensePaymentMethod::Director,
            amount: '15000.0000',
            description: 'Site visit fuel',
        );
        $j2->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j2, $poster);

        // Execute Report
        $reportService = app(DirectorExpenseLedgerReport::class);
        $report = $reportService->forCompany($company, CarbonImmutable::parse('2026-08-01'), CarbonImmutable::parse('2026-08-31'));

        $this->assertSame('0.0000', $report['opening_due_to_director']);
        $this->assertSame('135000.0000', $report['total_funded_by_director']);
        $this->assertSame('0.0000', $report['total_reimbursed']);
        $this->assertSame('135000.0000', $report['closing_due_to_director']);
        $this->assertCount(2, $report['rows']);

        // Verify category breakdown
        $this->assertArrayHasKey('5300', $report['categories_summary']); // Rent
        $this->assertSame('120000.0000', $report['categories_summary']['5300']['total']);
        $this->assertArrayHasKey('5200', $report['categories_summary']); // Fuel
        $this->assertSame('15000.0000', $report['categories_summary']['5200']['total']);
    }

    public function test_bidding_expense_ledger_and_transfer_to_project(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'YM Construction Pvt Ltd', 'slug' => 'ymc-construction']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $maker = User::factory()->create();
        $poster = User::factory()->create();
        $maker->assignRole($role);
        $poster->assignRole($role);

        $period = $company->financialPeriods()->whereDate('starts_on', '<=', '2026-08-01')->whereDate('ends_on', '>=', '2026-08-01')->firstOrFail();
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
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
        $opening->lines()->create(['company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $equity->getKey(), 'debit' => '0.0000', 'credit' => '500000.0000']);
        $opening->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($opening, $poster);

        $quickExpense = app(RecordQuickExpenseAction::class);

        // 1. Record Tender Fee: PKR 25,000
        $j1 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-02'),
            category: ExpenseCategory::TenderFee,
            paymentMethod: ExpensePaymentMethod::Cash,
            amount: '25000.0000',
            description: 'DHA Phase 9 Tender Document purchase',
        );
        $j1->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j1, $poster);

        // 2. Record Site Visit Bidding: PKR 10,000
        $j2 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-03'),
            category: ExpenseCategory::SiteVisitBidding,
            paymentMethod: ExpensePaymentMethod::Cash,
            amount: '10000.0000',
            description: 'Pre-bid site inspection trip',
        );
        $j2->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j2, $poster);

        // Verify Bidding Report
        $reportService = app(BiddingExpenseLedgerReport::class);
        $report = $reportService->forCompany($company, CarbonImmutable::parse('2026-08-01'), CarbonImmutable::parse('2026-08-31'));

        $this->assertSame('35000.0000', $report['total_bidding_expense']);
        $this->assertCount(2, $report['rows']);
        $this->assertArrayHasKey('5051', $report['subhead_summary']); // Tender Fee
        $this->assertSame('25000.0000', $report['subhead_summary']['5051']['total']);

        // 3. Bid is WON -> Transfer Bidding Cost to Project
        $project = Project::factory()->for($company)->create(['name' => 'DHA Phase 9 Commercial Plaza']);

        $transferAction = app(TransferBiddingCostToProjectAction::class);
        $transferJournal = $transferAction->handle(
            company: $company,
            actor: $maker,
            project: $project,
            date: CarbonImmutable::parse('2026-08-10'),
            amount: '35000.0000',
            description: 'Transfer DHA Phase 9 pre-bid costs',
        );

        $this->assertNotNull($transferJournal);
        $this->assertCount(2, $transferJournal->lines);

        $debitLine = $transferJournal->lines->firstWhere('debit', '>', 0);
        $creditLine = $transferJournal->lines->firstWhere('credit', '>', 0);

        $this->assertSame($project->getKey(), $debitLine->project_id);
        $this->assertSame('35000.0000', $debitLine->debit);
        $this->assertSame('35000.0000', $creditLine->credit);
    }

    public function test_pages_render_and_support_presets(): void
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

        Livewire::test(DirectorExpenseLedgerPage::class)
            ->assertSuccessful()
            ->call('setLastMonth')
            ->assertSet('fromDate', today()->subMonth()->startOfMonth()->toDateString());

        Livewire::test(BiddingExpenseLedgerPage::class)
            ->assertSuccessful()
            ->call('setThisMonth')
            ->assertSet('fromDate', today()->startOfMonth()->toDateString());
    }
}
