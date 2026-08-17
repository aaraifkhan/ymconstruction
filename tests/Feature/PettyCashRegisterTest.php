<?php

namespace Tests\Feature;

use App\Actions\Accounting\PerformPettyCashReconciliationAction;
use App\Actions\Accounting\PostJournalEntryAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\RecordPettyCashTopUpAction;
use App\Actions\Accounting\RecordQuickExpenseAction;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Filament\Pages\PettyCashRegisterPage;
use App\Models\Company;
use App\Models\User;
use App\Reports\PettyCashRegisterReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PettyCashRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_petty_cash_top_up_and_expense_lifecycle(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'BM Construction Pvt Ltd', 'slug' => 'bmc-construction']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $maker = User::factory()->create();
        $poster = User::factory()->create();
        $maker->assignRole($role);
        $poster->assignRole($role);

        // 1. Top-up from Director: PKR 50,000
        $topUpAction = app(RecordPettyCashTopUpAction::class);
        $topUpJournal = $topUpAction->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-05'),
            amount: '50000.0000',
            sourceType: 'director',
            description: 'Petty cash initial float funded by Director',
        );

        $this->assertNotNull($topUpJournal);
        $this->assertSame('1112', $topUpJournal->lines->firstWhere('debit', '>', 0)->account_code_snapshot); // Site Petty Cash
        $this->assertSame('2220', $topUpJournal->lines->firstWhere('credit', '>', 0)->account_code_snapshot); // Director Loan (Due to Director)

        // Post top-up
        $topUpJournal->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($topUpJournal, $poster);

        // 2. Spend from Petty Cash: PKR 3,500 for Entertainment/Kitchen
        $expenseAction = app(RecordQuickExpenseAction::class);
        $expenseJournal = $expenseAction->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-06'),
            category: ExpenseCategory::Entertainment,
            paymentMethod: ExpensePaymentMethod::PettyCash,
            amount: '3500.0000',
            description: 'Tea, milk carton & refreshments for site office',
        );

        // Post expense
        $expenseJournal->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($expenseJournal, $poster);

        // 3. Verify Register Report
        $reportService = app(PettyCashRegisterReport::class);
        $report = $reportService->forCompany($company, CarbonImmutable::parse('2026-08-01'), CarbonImmutable::parse('2026-08-31'));

        $this->assertSame('0.0000', $report['opening_balance']);
        $this->assertSame('50000.0000', $report['inflow_total']);
        $this->assertSame('3500.0000', $report['outflow_total']);
        $this->assertSame('46500.0000', $report['closing_balance']);
        $this->assertCount(2, $report['rows']);
    }

    public function test_petty_cash_physical_reconciliation_calculation(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $maker = User::factory()->create();
        $poster = User::factory()->create();
        $maker->assignRole($role);
        $poster->assignRole($role);

        // Create float: PKR 20,000
        $topUpJournal = app(RecordPettyCashTopUpAction::class)->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-01'),
            amount: '20000.0000',
            sourceType: 'head_office_cash',
            description: 'Float',
        );
        $topUpJournal->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($topUpJournal, $poster);

        // Physical count on 2026-08-05:
        // System expected: PKR 20,000
        // Physical cash in hand: PKR 14,000
        // On-account cash with site supervisor: PKR 6,000
        // Difference = 20,000 - 6,000 - 14,000 = 0.0000
        $reconciliationAction = app(PerformPettyCashReconciliationAction::class);
        $rec = $reconciliationAction->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-05'),
            physicalCountedCash: '14000.0000',
            onAccountHeld: '6000.0000',
            explanation: 'Routine weekly audit',
        );

        $this->assertSame('20000.0000', $rec->system_expected_balance);
        $this->assertSame('6000.0000', $rec->on_account_held);
        $this->assertSame('14000.0000', $rec->physical_counted_cash);
        $this->assertSame('0.0000', $rec->difference);
    }

    public function test_petty_cash_register_page_renders_and_supports_presets(): void
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

        Livewire::test(PettyCashRegisterPage::class)
            ->assertSuccessful()
            ->call('setLastMonth')
            ->assertSet('fromDate', today()->subMonth()->startOfMonth()->toDateString())
            ->assertSet('toDate', today()->subMonth()->endOfMonth()->toDateString())
            ->call('setThisMonth')
            ->assertSet('fromDate', today()->startOfMonth()->toDateString())
            ->assertSet('toDate', today()->toDateString());
    }
}
