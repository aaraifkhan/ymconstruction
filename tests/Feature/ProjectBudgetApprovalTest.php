<?php

namespace Tests\Feature;

use App\Actions\Projects\ApproveProjectBudgetAction;
use App\Enums\PartyRole;
use App\Enums\ProjectBudgetStatus;
use App\Models\Company;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectBudgetLine;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectBudgetApprovalTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_approval_snapshots_total_actor_and_audit_evidence(): void
    {
        [$budget, $preparer, $approver] = $this->draftBudget();
        ProjectBudgetLine::factory()->create([
            'company_id' => $budget->company_id,
            'project_budget_id' => $budget->getKey(),
            'amount' => '100.1234',
        ]);
        ProjectBudgetLine::factory()->create([
            'company_id' => $budget->company_id,
            'project_budget_id' => $budget->getKey(),
            'amount' => '50.8766',
        ]);

        app(ApproveProjectBudgetAction::class)->handle($budget, $approver);
        $budget->refresh();

        $this->assertSame(ProjectBudgetStatus::Approved, $budget->status);
        $this->assertSame('151.0000', $budget->total_amount);
        $this->assertSame($preparer->getKey(), $budget->prepared_by_id);
        $this->assertSame($approver->getKey(), $budget->approved_by_id);
        $this->assertNotNull($budget->approved_at);
        $this->assertTrue(
            Activity::query()
                ->where('log_name', 'project_budgets')
                ->where('event', 'approved')
                ->where('subject_id', $budget->getKey())
                ->exists(),
        );
    }

    public function test_preparer_cannot_approve_their_own_budget(): void
    {
        [$budget, $preparer] = $this->draftBudget();
        $preparer->givePermissionTo(Permission::findOrCreate('Approve:ProjectBudget'));
        $preparer->companies()->attach($budget->company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        ProjectBudgetLine::factory()->create([
            'company_id' => $budget->company_id,
            'project_budget_id' => $budget->getKey(),
        ]);

        $this->actingAs($preparer);
        Filament::setTenant($budget->company);
        Filament::bootCurrentPanel();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('preparer cannot approve');

        app(ApproveProjectBudgetAction::class)->handle($budget, $preparer);
    }

    public function test_new_approved_version_supersedes_previous_approved_budget(): void
    {
        [$firstBudget, , $approver] = $this->draftBudget();
        ProjectBudgetLine::factory()->create([
            'company_id' => $firstBudget->company_id,
            'project_budget_id' => $firstBudget->getKey(),
            'amount' => '100.0000',
        ]);
        app(ApproveProjectBudgetAction::class)->handle($firstBudget, $approver);

        $secondPreparer = User::factory()->create();
        $secondBudget = ProjectBudget::factory()->create([
            'company_id' => $firstBudget->company_id,
            'project_id' => $firstBudget->project_id,
            'version' => 2,
            'prepared_by_id' => $secondPreparer->getKey(),
        ]);
        ProjectBudgetLine::factory()->create([
            'company_id' => $secondBudget->company_id,
            'project_budget_id' => $secondBudget->getKey(),
            'amount' => '250.0000',
        ]);

        app(ApproveProjectBudgetAction::class)->handle($secondBudget, $approver);

        $this->assertSame(ProjectBudgetStatus::Superseded, $firstBudget->refresh()->status);
        $this->assertSame(ProjectBudgetStatus::Approved, $secondBudget->refresh()->status);
        $this->assertSame('250.0000', $secondBudget->total_amount);
    }

    public function test_approved_budget_header_and_lines_are_immutable(): void
    {
        [$budget, , $approver] = $this->draftBudget();
        $line = ProjectBudgetLine::factory()->create([
            'company_id' => $budget->company_id,
            'project_budget_id' => $budget->getKey(),
        ]);
        app(ApproveProjectBudgetAction::class)->handle($budget, $approver);

        try {
            $budget->update(['notes' => 'Changed after approval']);
            $this->fail('Approved budget header was changed.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('immutable');

        $line->update(['amount' => '999.0000']);
    }

    public function test_budget_lines_reject_cross_company_dimensions(): void
    {
        [$budget] = $this->draftBudget();
        Filament::setTenant(null);

        $otherCompany = Company::factory()->create();
        $otherClient = Party::factory()->forCompany($otherCompany)->withRoles(PartyRole::Customer)->create();
        $otherProject = Project::factory()->make([
            'company_id' => $otherCompany->getKey(),
            'client_party_id' => $otherClient->getKey(),
        ]);
        $otherProject->save();
        $otherBudget = ProjectBudget::factory()->create([
            'company_id' => $otherCompany->getKey(),
            'project_id' => $otherProject->getKey(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('same company');

        ProjectBudgetLine::factory()->create([
            'company_id' => $budget->company_id,
            'project_budget_id' => $otherBudget->getKey(),
        ]);
    }

    /**
     * @return array{ProjectBudget, User, User}
     */
    private function draftBudget(): array
    {
        $company = Company::factory()->create();
        $project = Project::factory()->create(['company_id' => $company->getKey()]);
        $preparer = User::factory()->create();
        $approver = User::factory()->create();
        $approver->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        $approver->givePermissionTo(Permission::findOrCreate('Approve:ProjectBudget'));

        $budget = ProjectBudget::factory()->create([
            'company_id' => $company->getKey(),
            'project_id' => $project->getKey(),
            'prepared_by_id' => $preparer->getKey(),
        ]);

        $this->actingAs($approver);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        return [$budget, $preparer, $approver];
    }
}
