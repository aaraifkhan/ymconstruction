<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\RecordQuickExpenseAction;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Filament\Pages\QuickExpenseEntryPage;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuickExpenseEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_quick_expense_action_records_cash_expense(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'YM Construction Pvt Ltd', 'slug' => 'ymc-construction']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);

        $action = app(RecordQuickExpenseAction::class);
        $journal = $action->handle(
            company: $company,
            actor: $user,
            date: CarbonImmutable::parse('2026-08-10'),
            category: ExpenseCategory::Fuel,
            paymentMethod: ExpensePaymentMethod::Cash,
            amount: '12500.0000',
            description: 'Site generator fuel purchase',
        );

        $this->assertNotNull($journal);
        $this->assertSame(VoucherType::Payment, $journal->voucher_type);
        $this->assertSame(JournalStatus::Submitted, $journal->status);
        $this->assertCount(2, $journal->lines);

        $debitLine = $journal->lines->firstWhere('debit', '>', 0);
        $creditLine = $journal->lines->firstWhere('credit', '>', 0);

        $this->assertSame('5200', $debitLine->account_code_snapshot); // Fuel
        $this->assertSame('12500.0000', $debitLine->debit);
        $this->assertSame('1111', $creditLine->account_code_snapshot); // Head Office Cash
        $this->assertSame('12500.0000', $creditLine->credit);
    }

    public function test_quick_expense_action_records_director_funded_expense_as_due_to_director(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'BM Construction Pvt Ltd', 'slug' => 'bmc-construction']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);

        $action = app(RecordQuickExpenseAction::class);
        $journal = $action->handle(
            company: $company,
            actor: $user,
            date: CarbonImmutable::parse('2026-08-12'),
            category: ExpenseCategory::OfficeRent,
            paymentMethod: ExpensePaymentMethod::Director,
            amount: '85000.0000',
            description: 'Office rent paid by Director personally',
        );

        $this->assertNotNull($journal);
        $this->assertSame(VoucherType::Journal, $journal->voucher_type);

        $debitLine = $journal->lines->firstWhere('debit', '>', 0);
        $creditLine = $journal->lines->firstWhere('credit', '>', 0);

        $this->assertSame('5300', $debitLine->account_code_snapshot); // Office Rent
        $this->assertSame('85000.0000', $debitLine->debit);
        $this->assertSame('2220', $creditLine->account_code_snapshot); // Director Loan (Due to Director)
        $this->assertSame('85000.0000', $creditLine->credit);
    }

    public function test_quick_expense_action_records_petty_cash_with_shared_entity_tag(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $bmc = Company::factory()->create(['name' => 'BM Construction', 'slug' => 'bmc']);
        $orbit = Company::factory()->create(['name' => '7-Orbit IT', 'slug' => '7-orbit']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($bmc, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);

        $action = app(RecordQuickExpenseAction::class);
        $journal = $action->handle(
            company: $bmc,
            actor: $user,
            date: CarbonImmutable::parse('2026-08-12'),
            category: ExpenseCategory::DigitalAdSpend,
            paymentMethod: ExpensePaymentMethod::PettyCash,
            amount: '25000.0000',
            description: 'Meta Ads for Instagram & FB',
            expenseOfCompanyId: $orbit->getKey(),
        );

        $debitLine = $journal->lines->firstWhere('debit', '>', 0);
        $creditLine = $journal->lines->firstWhere('credit', '>', 0);

        $this->assertSame('6250', $debitLine->account_code_snapshot); // Digital Ad Spend
        $this->assertSame($orbit->getKey(), $debitLine->related_company_id);
        $this->assertSame('1112', $creditLine->account_code_snapshot); // Site Petty Cash
    }

    public function test_quick_expense_action_enforces_project_selection_for_direct_project_costs(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);

        $action = app(RecordQuickExpenseAction::class);

        $this->expectException(ValidationException::class);
        $action->handle(
            company: $company,
            actor: $user,
            date: CarbonImmutable::parse('2026-08-10'),
            category: ExpenseCategory::Cement,
            paymentMethod: ExpensePaymentMethod::Cash,
            amount: '45000.0000',
            description: 'Cement bags for site (missing project)',
            projectId: null,
        );
    }

    public function test_quick_expense_entry_page_submits_and_resets(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $project = Project::factory()->for($company)->create(['name' => 'C-21 DHA Margala']);

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(QuickExpenseEntryPage::class)
            ->assertSuccessful()
            ->set('data.transaction_date', '2026-08-10')
            ->set('data.category', ExpenseCategory::Excavation->value)
            ->set('data.payment_method', ExpensePaymentMethod::Cash->value)
            ->set('data.amount', '75000')
            ->set('data.project_id', $project->getKey())
            ->set('data.description', 'Excavation payment to GAB Earthworks')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $company->getKey(),
            'description' => 'Excavation & Earthworks: Excavation payment to GAB Earthworks',
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'company_id' => $company->getKey(),
            'project_id' => $project->getKey(),
            'debit' => 75000,
            'description' => 'Excavation payment to GAB Earthworks',
        ]);
    }
}
