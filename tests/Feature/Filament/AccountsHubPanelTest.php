<?php

namespace Tests\Feature\Filament;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Filament\Pages\GeneralGroupExpensePage;
use App\Filament\Pages\MasterAccountsHubPage;
use App\Filament\Pages\QuickExpenseEntryPage;
use App\Filament\Pages\SharedCostAllocationPage;
use App\Filament\Widgets\QuickExpenseStatsWidget;
use App\Models\Company;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\CompanySeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountsHubPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
    }

    public function test_unauthenticated_guests_redirect_to_login_when_accessing_accounts_hub(): void
    {
        $this->get('/accounts-hub')
            ->assertRedirect(route('filament.accounts-hub.auth.login'));
    }

    public function test_unauthorized_user_is_forbidden_on_portal_accounts_hub_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('portal.accounts-hub'))
            ->assertForbidden();
    }

    public function test_authorized_user_sees_accounts_hub_card_on_portal_and_can_redirect(): void
    {
        $this->seed(CompanySeeder::class);
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user)
            ->get(route('portal'))
            ->assertOk()
            ->assertSee('Accounts Hub &amp; Fast Entry', false)
            ->assertSee('Universal cross-company expense &amp; fund hub', false);

        $this->actingAs($user)
            ->get(route('portal.accounts-hub'))
            ->assertRedirect(Filament::getPanel('accounts-hub')->getUrl());
    }

    public function test_user_with_view_master_accounts_hub_permission_can_access_hub(): void
    {
        $company = $this->provisionCompany('Alpha Construction');
        $user = User::factory()->create();
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:MasterAccountsHub'));

        $this->actingAs($user)
            ->get(route('portal.accounts-hub'))
            ->assertRedirect(Filament::getPanel('accounts-hub')->getUrl());

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        $this->assertTrue(MasterAccountsHubPage::canAccess());
    }

    public function test_accounts_hub_panel_sidebar_navigation(): void
    {
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        $navigation = Filament::getPanel('accounts-hub')->getNavigation();
        $groupLabels = collect($navigation)->map(fn ($g) => $g->getLabel())->all();

        $this->assertContains('Accounts Hub & Fast Entry', $groupLabels);
        $this->assertContains('Portal', $groupLabels);

        $hubGroup = collect($navigation)->first(fn ($g) => $g->getLabel() === 'Accounts Hub & Fast Entry');
        $itemLabels = collect($hubGroup->getItems())->map(fn ($i) => $i->getLabel())->all();

        $this->assertContains('Master Accounts Hub', $itemLabels);
        $this->assertContains('Quick Expense Entry', $itemLabels);
        $this->assertContains('General & Group Expenses', $itemLabels);
        $this->assertContains('Shared Cost Allocation', $itemLabels);

        $portalGroup = collect($navigation)->first(fn ($g) => $g->getLabel() === 'Portal');
        $this->assertSame('Back to Access Portal', $portalGroup->getItems()[0]->getLabel());
        $this->assertSame(route('portal'), $portalGroup->getItems()[0]->getUrl());
    }

    public function test_can_post_general_group_expense_from_accounts_hub_panel(): void
    {
        $corporateCompany = $this->provisionCompany('7 Orbit Corporate');
        $operatingCompany = $this->provisionCompany('BMC Construction');

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(GeneralGroupExpensePage::class)
            ->assertOk()
            ->set('data.target_company_id', $corporateCompany->getKey())
            ->set('data.transaction_date', '2026-08-15')
            ->set('data.category', ExpenseCategory::Entertainment->value)
            ->set('data.payment_method', ExpensePaymentMethod::Director->value)
            ->set('data.amount', '1500.00')
            ->set('data.description', 'Staff chai & refreshments for all departments')
            ->call('submit');

        $entry = JournalEntry::withoutGlobalScopes()->where('company_id', $corporateCompany->getKey())->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame($corporateCompany->getKey(), $entry->company_id);
        $this->assertSame(JournalStatus::Submitted, $entry->status);
        $this->assertSame('1500.0000', $entry->debit_total);

        // Verify operating company has zero entries from this general expense
        $operatingEntriesCount = JournalEntry::withoutGlobalScopes()->where('company_id', $operatingCompany->getKey())->count();
        $this->assertSame(0, $operatingEntriesCount);
    }

    public function test_can_post_quick_expense_from_accounts_hub_panel(): void
    {
        $companyA = $this->provisionCompany('Alpha Corp');
        $companyB = $this->provisionCompany('Beta Corp');

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        $periodB = FinancialPeriod::withoutGlobalScopes()->where('company_id', $companyB->getKey())->firstOrFail();

        Livewire::test(QuickExpenseEntryPage::class)
            ->assertOk()
            ->set('data.target_company_id', $companyB->getKey())
            ->set('data.transaction_date', '2026-08-10')
            ->set('data.category', ExpenseCategory::Fuel->value)
            ->set('data.payment_method', ExpensePaymentMethod::Director->value)
            ->set('data.amount', '2500.00')
            ->set('data.description', 'Site generator fuel for Beta Corp')
            ->call('submit');

        $entry = JournalEntry::withoutGlobalScopes()->where('company_id', $companyB->getKey())->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame($companyB->getKey(), $entry->company_id);
        $this->assertSame(JournalStatus::Submitted, $entry->status);
        $this->assertSame('2500.0000', $entry->debit_total);
    }

    public function test_can_post_shared_cost_allocation_from_accounts_hub_panel(): void
    {
        $companyA = $this->provisionCompany('Head Office Corp');
        $companyB = $this->provisionCompany('Branch Corp');

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(SharedCostAllocationPage::class)
            ->assertOk()
            ->set('data.paying_company_id', $companyA->getKey())
            ->set('data.date', '2026-08-01')
            ->set('data.category', ExpenseCategory::Utilities->value)
            ->set('data.payment_method', ExpensePaymentMethod::Cash->value)
            ->set('data.total_amount', '10000.00')
            ->set('data.description', 'Monthly electricity bill split')
            ->set('data.shares', [
                ['company_id' => $companyA->getKey(), 'amount' => '6000.00'],
                ['company_id' => $companyB->getKey(), 'amount' => '4000.00'],
            ])
            ->call('submit');

        // Verify paying company entry
        $payingEntry = JournalEntry::withoutGlobalScopes()->where('company_id', $companyA->getKey())->latest('id')->first();
        $this->assertNotNull($payingEntry);
        $this->assertSame('10000.0000', $payingEntry->debit_total);

        // Verify recipient company entry
        $recipientEntry = JournalEntry::withoutGlobalScopes()->where('company_id', $companyB->getKey())->latest('id')->first();
        $this->assertNotNull($recipientEntry);
        $this->assertSame('4000.0000', $recipientEntry->debit_total);
    }

    public function test_quick_expense_stats_widget_renders_in_hub_panel(): void
    {
        $company = $this->provisionCompany('Widget Test Corp');
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(QuickExpenseStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('Cash in Hand Available')
            ->assertSee('Bank Balance Available')
            ->assertSee("Today's Expenses");
    }

    private function provisionCompany(string $name): Company
    {
        $company = Company::factory()->create(['name' => $name]);
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));

        return $company;
    }
}
