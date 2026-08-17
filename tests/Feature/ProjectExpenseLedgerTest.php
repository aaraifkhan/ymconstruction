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
use App\Filament\Pages\ProjectExpenseLedgerPage;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Reports\ProjectExpenseLedgerReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectExpenseLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_project_expense_ledger_calculates_materials_labor_and_overheads(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create(['name' => 'YM Construction Pvt Ltd', 'slug' => 'ymc-construction']);
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $project = Project::factory()->for($company)->create(['name' => 'C-21 DHA Margala']);

        $role = Role::findOrCreate('super_admin');
        $maker = User::factory()->create();
        $poster = User::factory()->create();
        $maker->assignRole($role);
        $poster->assignRole($role);

        $quickExpense = app(RecordQuickExpenseAction::class);

        // 1. Material: Cement PKR 150,000
        $j1 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-05'),
            category: ExpenseCategory::Cement,
            paymentMethod: ExpensePaymentMethod::Cash,
            amount: '150000.0000',
            description: '150 Cement bags from Bestway',
            projectId: $project->getKey(),
        );
        $j1->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j1, $poster);

        // 2. Labor: Site Labor PKR 45,000
        $j2 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-06'),
            category: ExpenseCategory::Labor,
            paymentMethod: ExpensePaymentMethod::Cash,
            amount: '45000.0000',
            description: 'Weekly masons & labor wages',
            projectId: $project->getKey(),
        );
        $j2->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j2, $poster);

        // 3. Overhead: Site Utilities PKR 12,000
        $j3 = $quickExpense->handle(
            company: $company,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-07'),
            category: ExpenseCategory::SiteUtilities,
            paymentMethod: ExpensePaymentMethod::Cash,
            amount: '12000.0000',
            description: 'Water bowser tankers for curing',
            projectId: $project->getKey(),
        );
        $j3->update(['status' => JournalStatus::Approved, 'approved_by_id' => $poster->getKey()]);
        app(PostJournalEntryAction::class)->handle($j3, $poster);

        // Execute Report
        $reportService = app(ProjectExpenseLedgerReport::class);
        $report = $reportService->forProject($company, $project);

        $this->assertSame('207000.0000', $report['total_project_cost']);
        $this->assertSame('150000.0000', $report['total_materials']);
        $this->assertSame('45000.0000', $report['total_labor_equipment']);
        $this->assertSame('12000.0000', $report['total_site_overheads']);
        $this->assertCount(3, $report['rows']);
    }

    public function test_project_expense_ledger_page_renders_and_switches_project(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $p1 = Project::factory()->for($company)->create(['name' => 'Project Alpha']);
        $p2 = Project::factory()->for($company)->create(['name' => 'Project Beta']);

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(ProjectExpenseLedgerPage::class)
            ->assertSuccessful()
            ->assertSet('selectedProjectId', $p1->getKey())
            ->set('selectedProjectId', $p2->getKey())
            ->assertSet('selectedProjectId', $p2->getKey());
    }
}
