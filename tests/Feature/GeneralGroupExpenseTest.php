<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Filament\Pages\GeneralGroupExpensePage;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GeneralGroupExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
    }

    public function test_super_admin_can_access_general_group_expense_page(): void
    {
        $corporate = $this->provisionCompany('7 Orbit Corporate');
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(GeneralGroupExpensePage::class)
            ->assertSuccessful()
            ->assertSee('Record Combined / General Group Expense')
            ->assertSee('General Expense Head / Shared Asset');
    }

    public function test_can_record_staff_tea_expense_funded_by_director(): void
    {
        $corporate = $this->provisionCompany('7 Orbit Corporate');
        $bmc = $this->provisionCompany('BMC Construction');

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(GeneralGroupExpensePage::class)
            ->assertOk()
            ->set('data.target_company_id', $corporate->getKey())
            ->set('data.transaction_date', '2026-08-18')
            ->set('data.category', ExpenseCategory::Entertainment->value)
            ->set('data.payment_method', ExpensePaymentMethod::Director->value)
            ->set('data.amount', '1800.00')
            ->set('data.description', 'Daily tea, milk cartons and sugar for all department staff')
            ->call('submit');

        // Verify journal entry in corporate entity
        $entry = JournalEntry::withoutGlobalScopes()
            ->where('company_id', $corporate->getKey())
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame(JournalStatus::Submitted, $entry->status);
        $this->assertSame('1800.0000', $entry->debit_total);
        $this->assertSame('1800.0000', $entry->credit_total);
        $this->assertStringContainsString('Entertainment', $entry->description);
        $this->assertStringContainsString('tea, milk cartons', $entry->description);

        // Check line accounts
        $debitLine = $entry->lines()->where('debit', '>', 0)->first();
        $creditLine = $entry->lines()->where('credit', '>', 0)->first();

        $this->assertSame('6700', $debitLine->account_code_snapshot);
        $this->assertSame('2220', $creditLine->account_code_snapshot);

        // Verify operating company has zero entries
        $bmcEntriesCount = JournalEntry::withoutGlobalScopes()->where('company_id', $bmc->getKey())->count();
        $this->assertSame(0, $bmcEntriesCount);
    }

    public function test_can_record_shared_fixed_asset_purchase_via_bank(): void
    {
        $corporate = $this->provisionCompany('7 Orbit Corporate');

        $bankAccount = CompanyBankAccount::create([
            'company_id' => $corporate->getKey(),
            'bank_name' => 'Meezan Corporate Bank',
            'account_title' => '7 Orbit Main Ops',
            'account_number' => '1234567890',
            'currency_code' => 'PKR',
            'is_active' => true,
        ]);

        app(ProvisionCompanyAccountingFoundationAction::class)->handle($corporate, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(GeneralGroupExpensePage::class)
            ->assertOk()
            ->set('data.target_company_id', $corporate->getKey())
            ->set('data.transaction_date', '2026-08-19')
            ->set('data.category', ExpenseCategory::FixedAssetPurchase->value)
            ->set('data.payment_method', ExpensePaymentMethod::Bank->value)
            ->set('data.company_bank_account_id', $bankAccount->getKey())
            ->set('data.amount', '85000.00')
            ->set('data.description', 'New 1.5 Ton Inverter AC for shared head office hall')
            ->call('submit');

        $entry = JournalEntry::withoutGlobalScopes()
            ->where('company_id', $corporate->getKey())
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('85000.0000', $entry->debit_total);

        $debitLine = $entry->lines()->where('debit', '>', 0)->first();
        $this->assertSame('1280', $debitLine->account_code_snapshot);
    }

    private function provisionCompany(string $name): Company
    {
        $company = Company::factory()->create(['name' => $name]);
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));

        return $company;
    }
}
