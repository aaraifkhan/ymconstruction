<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Enums\ExpensePaymentMethod;
use App\Enums\IncomeCategory;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Filament\Pages\QuickIncomeEntryPage;
use App\Filament\Widgets\QuickIncomeStatsWidget;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuickIncomeEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_quick_income_entry_page_submits_and_records_bank_receipt(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'YM Construction Pvt Ltd', 'slug' => 'ymc-construction']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $bankAccount = CompanyBankAccount::factory()->create(['company_id' => $company, 'is_active' => true]);
        $project = Project::factory()->for($company)->create(['name' => 'C-21 DHA Margala']);

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(QuickIncomeEntryPage::class)
            ->assertSuccessful()
            ->set('data.transaction_date', '2026-08-10')
            ->set('data.income_category', IncomeCategory::CustomerReceipt->value)
            ->set('data.receiving_method', ExpensePaymentMethod::Bank->value)
            ->set('data.company_bank_account_id', $bankAccount->getKey())
            ->set('data.amount', '150000')
            ->set('data.project_id', $project->getKey())
            ->set('data.description', 'Milestone 2 payment received via online transfer')
            ->call('submit')
            ->assertHasNoErrors();

        $entry = JournalEntry::withoutGlobalScopes()->where('company_id', $company->getKey())->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame(VoucherType::Receipt, $entry->voucher_type);
        $this->assertSame(JournalStatus::Submitted, $entry->status);
        $this->assertSame('150000.0000', $entry->debit_total);
        $this->assertCount(2, $entry->lines);

        $debitLine = $entry->lines->firstWhere('debit', '>', 0);
        $creditLine = $entry->lines->firstWhere('credit', '>', 0);

        $this->assertSame($bankAccount->getKey(), $debitLine->company_bank_account_id);
        $this->assertSame('150000.0000', $debitLine->debit);
        $this->assertSame($project->getKey(), $creditLine->project_id);
        $this->assertSame('150000.0000', $creditLine->credit);
    }

    public function test_quick_income_entry_page_submits_cash_receipt(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => '7-Orbit IT', 'slug' => '7-orbit']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::ItServices, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(QuickIncomeEntryPage::class)
            ->assertSuccessful()
            ->set('data.transaction_date', '2026-08-11')
            ->set('data.income_category', IncomeCategory::ServiceRevenue->value)
            ->set('data.receiving_method', ExpensePaymentMethod::Cash->value)
            ->set('data.amount', '35000')
            ->set('data.description', 'Cash fee received for web portal consulting')
            ->call('submit')
            ->assertHasNoErrors();

        $entry = JournalEntry::withoutGlobalScopes()->where('company_id', $company->getKey())->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame(VoucherType::Receipt, $entry->voucher_type);
        $this->assertSame('35000.0000', $entry->debit_total);

        $debitLine = $entry->lines->firstWhere('debit', '>', 0);
        $creditLine = $entry->lines->firstWhere('credit', '>', 0);

        $this->assertSame('1111', $debitLine->account_code_snapshot);
        $this->assertSame('35000.0000', $debitLine->debit);
        $this->assertSame('4200', $creditLine->account_code_snapshot); // IT Services Revenue
    }

    public function test_quick_income_stats_widget_renders_in_page(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'Widget Income Corp']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(QuickIncomeStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('Cash in Hand Available')
            ->assertSee('Bank Balance Available')
            ->assertSee("Today's Income & Receipts");
    }
}
