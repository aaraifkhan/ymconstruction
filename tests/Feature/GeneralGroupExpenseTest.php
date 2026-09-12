<?php

namespace Tests\Feature;

use App\Actions\Accounting\CheckAccountAvailableBalanceAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingMappingKey;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Filament\Pages\GeneralGroupExpensePage;
use App\Models\AccountingMapping;
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

    public function test_can_top_up_general_petty_cash_float(): void
    {
        $corporate = $this->provisionCompany('7 Orbit Corporate');
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        // Independent poster so maker-checker can complete immediately.
        User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        $balanceBefore = app(CheckAccountAvailableBalanceAction::class)
            ->getAccountBalance(
                $corporate,
                $corporate->accounts()->where('code', '1112')->firstOrFail(),
            );

        Livewire::test(GeneralGroupExpensePage::class)
            ->assertOk()
            ->callAction('topUpGeneralFloat', [
                'date' => '2026-08-19',
                'source_type' => 'director',
                'amount' => '5000',
                'description' => 'Petty cash injection',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $entry = JournalEntry::withoutGlobalScopes()
            ->where('company_id', $corporate->getKey())
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame(JournalStatus::Posted, $entry->status);
        $this->assertSame('5000.0000', $entry->debit_total);
        $this->assertNotNull($entry->voucher_number);

        $balanceAfter = app(CheckAccountAvailableBalanceAction::class)
            ->getAccountBalance(
                $corporate,
                $corporate->accounts()->where('code', '1112')->firstOrFail(),
            );

        $this->assertSame('0.0000', $balanceBefore);
        $this->assertSame('5000.0000', $balanceAfter);
    }

    public function test_top_up_heals_missing_site_petty_cash_mapping_from_account_code(): void
    {
        $corporate = $this->provisionCompany('7 Orbit');
        $corporate->update(['slug' => '7-orbit']);

        AccountingMapping::query()
            ->where('company_id', $corporate->getKey())
            ->where('system_key', AccountingMappingKey::SitePettyCash)
            ->delete();

        $pettyCash = $corporate->accounts()->where('code', '1112')->firstOrFail();
        $pettyCash->forceFill(['system_key' => null])->saveQuietly();

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(GeneralGroupExpensePage::class)
            ->assertOk()
            ->callAction('topUpGeneralFloat', [
                'date' => '2026-08-19',
                'source_type' => 'director',
                'amount' => '2500',
                'description' => 'Heal mapping top-up',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertNotNull(
            AccountingMapping::query()
                ->where('company_id', $corporate->getKey())
                ->where('system_key', AccountingMappingKey::SitePettyCash)
                ->where('account_id', $pettyCash->getKey())
                ->where('is_active', true)
                ->first()
        );

        $entry = JournalEntry::withoutGlobalScopes()
            ->where('company_id', $corporate->getKey())
            ->latest('id')
            ->first();

        $this->assertSame(JournalStatus::Posted, $entry->status);
        $this->assertSame('2500.0000', $entry->debit_total);
    }

    public function test_top_up_auto_provisions_missing_chart_of_accounts(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $corporate = Company::factory()->create([
            'name' => '7 Orbit',
            'slug' => '7-orbit',
            'is_active' => true,
        ]);

        $this->assertSame(0, $corporate->accounts()->count());
        $this->assertNull(
            AccountingMapping::query()
                ->where('company_id', $corporate->getKey())
                ->where('system_key', AccountingMappingKey::SitePettyCash)
                ->first()
        );

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(GeneralGroupExpensePage::class)
            ->assertOk()
            ->callAction('topUpGeneralFloat', [
                'date' => '2026-08-19',
                'source_type' => 'director',
                'amount' => '7500',
                'description' => 'Auto provision top-up',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertTrue($corporate->accounts()->where('code', '1112')->exists());
        $this->assertNotNull(
            AccountingMapping::query()
                ->where('company_id', $corporate->getKey())
                ->where('system_key', AccountingMappingKey::SitePettyCash)
                ->first()
        );

        $entry = JournalEntry::withoutGlobalScopes()
            ->where('company_id', $corporate->getKey())
            ->latest('id')
            ->first();

        $this->assertSame(JournalStatus::Posted, $entry->status);
        $this->assertSame('7500.0000', $entry->debit_total);
        $this->assertSame(
            '7500.0000',
            app(CheckAccountAvailableBalanceAction::class)->getAccountBalance(
                $corporate,
                $corporate->accounts()->where('code', '1112')->firstOrFail(),
            ),
        );
    }

    public function test_get_corporate_company_prefers_exact_7_orbit_slug(): void
    {
        $holding = $this->provisionCompany('7 Orbit');
        $holding->update(['slug' => '7-orbit']);
        $medical = $this->provisionCompany('7 Orbit Medical Billing');
        $medical->update(['slug' => '7-orbit-medical-billing']);

        $page = app(GeneralGroupExpensePage::class);

        $this->assertSame($holding->getKey(), $page->getCorporateCompany()?->getKey());
    }

    private function provisionCompany(string $name): Company
    {
        $company = Company::factory()->create(['name' => $name]);
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));

        return $company;
    }
}
