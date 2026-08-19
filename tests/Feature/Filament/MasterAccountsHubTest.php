<?php

namespace Tests\Feature\Filament;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Filament\Pages\MasterAccountsHubPage;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MasterAccountsHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
    }

    public function test_unauthorized_user_cannot_access_master_accounts_hub(): void
    {
        $companyA = $this->provisionCompany('Company A');
        $user = User::factory()->create();
        $user->companies()->attach($companyA, ['is_active' => true, 'can_access_descendants' => false]);

        $this->actingAs($user);
        Filament::setTenant($companyA);
        Filament::bootCurrentPanel();

        $this->assertFalse(MasterAccountsHubPage::canAccess());

        Livewire::test(MasterAccountsHubPage::class)
            ->assertForbidden();
    }

    public function test_authorized_user_can_access_master_accounts_hub_and_view_summaries(): void
    {
        $companyA = $this->provisionCompany('Company A');
        $companyB = $this->provisionCompany('Company B');

        $user = User::factory()->create();
        $user->companies()->attach($companyA, ['is_active' => true, 'can_access_descendants' => false]);
        $user->companies()->attach($companyB, ['is_active' => true, 'can_access_descendants' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:MasterAccountsHub'));

        $this->actingAs($user);
        Filament::setTenant($companyA);
        Filament::bootCurrentPanel();

        $this->assertTrue(MasterAccountsHubPage::canAccess());

        $component = Livewire::test(MasterAccountsHubPage::class)
            ->assertOk();

        $summaries = $component->get('companySummaries');
        $this->assertCount(2, $summaries);
        $this->assertSame('Company A', $summaries[0]['name']);
        $this->assertSame('Company B', $summaries[1]['name']);
    }

    public function test_can_record_quick_expense_for_another_company(): void
    {
        $companyA = $this->provisionCompany('Company A');
        $companyB = $this->provisionCompany('Company B');

        $user = User::factory()->create();
        $user->companies()->attach($companyA, ['is_active' => true, 'can_access_descendants' => false]);
        $user->companies()->attach($companyB, ['is_active' => true, 'can_access_descendants' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:MasterAccountsHub'));
        $user->givePermissionTo(Permission::findOrCreate('Create:JournalEntry'));
        $user->givePermissionTo(Permission::findOrCreate('Submit:JournalEntry'));

        $this->actingAs($user);
        Filament::setTenant($companyA);
        Filament::bootCurrentPanel();

        $periodB = FinancialPeriod::withoutGlobalScopes()->where('company_id', $companyB->getKey())->firstOrFail();

        $component = Livewire::test(MasterAccountsHubPage::class);
        $component->assertOk();

        $component
            ->set('data.target_company_id', $companyB->getKey())
            ->set('data.entry_type', 'expense')
            ->set('data.transaction_date', '2026-07-20')
            ->set('data.financial_period_id', $periodB->getKey())
            ->set('data.category', ExpenseCategory::Stationery->value)
            ->set('data.payment_method', ExpensePaymentMethod::Director->value)
            ->set('data.amount', '1500.00')
            ->set('data.description', 'Office stationary bought by director for Company B')
            ->call('submit');

        // Verify journal entry was created directly in Company B
        $entry = JournalEntry::withoutGlobalScopes()->where('company_id', $companyB->getKey())->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame($companyB->getKey(), $entry->company_id);
        $this->assertSame(JournalStatus::Submitted, $entry->status);
        $this->assertSame('1500.0000', $entry->debit_total);
        $this->assertSame(2, $entry->lines()->count());
    }

    public function test_can_record_multi_line_journal_for_another_company(): void
    {
        $companyA = $this->provisionCompany('Company A');
        $companyB = $this->provisionCompany('Company B');

        $user = User::factory()->create();
        $user->companies()->attach($companyA, ['is_active' => true, 'can_access_descendants' => false]);
        $user->companies()->attach($companyB, ['is_active' => true, 'can_access_descendants' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:MasterAccountsHub'));
        $user->givePermissionTo(Permission::findOrCreate('Create:JournalEntry'));
        $user->givePermissionTo(Permission::findOrCreate('Submit:JournalEntry'));

        $this->actingAs($user);
        Filament::setTenant($companyA);
        Filament::bootCurrentPanel();

        $periodB = FinancialPeriod::withoutGlobalScopes()->where('company_id', $companyB->getKey())->firstOrFail();
        $cashB = Account::withoutGlobalScopes()->where('company_id', $companyB->getKey())->where('code', '1111')->firstOrFail();
        $incomeB = Account::withoutGlobalScopes()->where('company_id', $companyB->getKey())->where('code', '4700')->firstOrFail();

        $component = Livewire::test(MasterAccountsHubPage::class);

        $lineKeys = array_keys($component->get('data.lines') ?? []);
        $k1 = $lineKeys[0] ?? '0';
        $k2 = $lineKeys[1] ?? '1';

        $component
            ->set('data.target_company_id', $companyB->getKey())
            ->set('data.entry_type', 'journal')
            ->set('data.voucher_type', VoucherType::Journal->value)
            ->set('data.transaction_date', '2026-07-20')
            ->set('data.financial_period_id', $periodB->getKey())
            ->set('data.journal_description', 'Company B opening cash receipt')
            ->set("data.lines.{$k1}.account_id", $cashB->getKey())
            ->set("data.lines.{$k1}.debit", 5000)
            ->set("data.lines.{$k1}.credit", 0)
            ->set("data.lines.{$k2}.account_id", $incomeB->getKey())
            ->set("data.lines.{$k2}.debit", 0)
            ->set("data.lines.{$k2}.credit", 5000)
            ->call('submit');

        // Verify journal entry in Company B
        $entry = JournalEntry::withoutGlobalScopes()->where('company_id', $companyB->getKey())->where('description', 'Company B opening cash receipt')->first();
        $this->assertNotNull($entry);
        $this->assertSame($companyB->getKey(), $entry->company_id);
        $this->assertSame(JournalStatus::Submitted, $entry->status);
        $this->assertSame('5000.0000', $entry->debit_total);
    }

    public function test_accounts_management_navigation_tree_renders_hub_and_fast_entry(): void
    {
        $company = $this->provisionCompany('Company Nav Test');
        $user = User::factory()->create();
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:MasterAccountsHub'));
        $user->givePermissionTo(Permission::findOrCreate('Create:JournalEntry'));

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        $panel = Filament::getCurrentPanel();
        $navigation = $panel->getNavigation();
        $accountsGroup = collect($navigation)->first(fn ($g) => $g->getLabel() === 'Accounts Management');
        $this->assertNotNull($accountsGroup, 'Accounts Management navigation group must exist');

        $parentLabels = collect($accountsGroup->getItems())->map(fn ($item) => $item->getLabel())->all();
        $this->assertContains('Accounts Hub & Fast Entry', $parentLabels);
        $this->assertContains('General Ledger & Vouchers', $parentLabels);
        $this->assertContains('Sales & Purchases (Billing)', $parentLabels);
        $this->assertContains('Banking & Treasury', $parentLabels);
        $this->assertContains('Fixed Assets & Depreciation', $parentLabels);
        $this->assertContains('Ledgers & Registers', $parentLabels);
        $this->assertContains('Financial & Operational Reports', $parentLabels);
        $this->assertContains('Accounting Setup & Rules', $parentLabels);

        $hubParent = collect($accountsGroup->getItems())->first(fn ($item) => $item->getLabel() === 'Accounts Hub & Fast Entry');
        $this->assertNotNull($hubParent, 'Accounts Hub & Fast Entry parent item must exist');

        $hubChildLabels = collect($hubParent->getChildItems())->map(fn ($c) => $c->getLabel())->all();
        $this->assertContains('Master Accounts Hub', $hubChildLabels);
        $this->assertContains('Quick Expense Entry', $hubChildLabels);
        $this->assertContains('Shared Cost Allocation', $hubChildLabels);

        $billingParent = collect($accountsGroup->getItems())->first(fn ($item) => $item->getLabel() === 'Sales & Purchases (Billing)');
        $this->assertNotNull($billingParent);
        $billingChildLabels = collect($billingParent->getChildItems())->map(fn ($c) => $c->getLabel())->all();
        $this->assertContains('Customer Invoices & Credit Notes', $billingChildLabels);
        $this->assertContains('Vendor Bills & Credit Notes', $billingChildLabels);

        $assetsParent = collect($accountsGroup->getItems())->first(fn ($item) => $item->getLabel() === 'Fixed Assets & Depreciation');
        $this->assertNotNull($assetsParent);
        $assetsChildLabels = collect($assetsParent->getChildItems())->map(fn ($c) => $c->getLabel())->all();
        $this->assertContains('Fixed Assets', $assetsChildLabels);
        $this->assertContains('Depreciation Runs', $assetsChildLabels);
    }

    private function provisionCompany(string $name): Company
    {
        $company = Company::factory()->create(['name' => $name]);
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));
        $periodsCount = FinancialPeriod::query()->where('company_id', $company->getKey())->count();
        $this->assertSame(12, $periodsCount, "Company {$name} (ID: {$company->getKey()}) must have 12 financial periods");

        return $company;
    }
}
