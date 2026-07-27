<?php

namespace Tests\Feature;

use App\Actions\Procurement\ApproveProcurementDocumentAction;
use App\Actions\Procurement\RejectProcurementDocumentAction;
use App\Actions\Procurement\SubmitPurchaseRequisitionAction;
use App\Enums\ProcurementApprovalStatus;
use App\Enums\ProcurementDocumentType;
use App\Enums\ProjectBudgetStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\Company;
use App\Models\Item;
use App\Models\ProcurementApprovalRule;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectBudgetLine;
use App\Models\ProjectSite;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchaseRequisitionWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_budget_checked_requisition_uses_configurable_sequential_approval_steps(): void
    {
        [$requisition, $line, $preparer] = $this->draftRequisition('250.0000', '10.0000', '5000.0000');
        $this->grant($preparer, $requisition->company, ['Submit:PurchaseRequisition']);

        foreach ([
            [1, 'Procurement Review', 'ApproveLevelOne:Procurement'],
            [2, 'Finance Review', 'ApproveLevelTwo:Procurement'],
        ] as [$step, $name, $permission]) {
            Permission::findOrCreate($permission, 'web');
            ProcurementApprovalRule::factory()->create([
                'company_id' => $requisition->company_id,
                'document_type' => ProcurementDocumentType::PurchaseRequisition,
                'step_number' => $step,
                'name' => $name,
                'permission_name' => $permission,
                'minimum_amount' => '1000.0000',
            ]);
        }

        $this->useTenant($preparer, $requisition->company);
        app(SubmitPurchaseRequisitionAction::class)->handle($requisition, $preparer);
        $requisition->refresh();

        $this->assertSame(PurchaseRequisitionStatus::Submitted, $requisition->status);
        $this->assertSame('2500.0000', $requisition->estimated_total);
        $this->assertSame('passed', $requisition->budget_check_status);
        $this->assertMatchesRegularExpression('/^PR-\d{4}-\d{6}$/', $requisition->requisition_number);
        $this->assertCount(2, $requisition->approvalSteps);

        $firstApprover = User::factory()->create();
        $this->grant($firstApprover, $requisition->company, [
            'Approve:PurchaseRequisition',
            'ApproveLevelOne:Procurement',
        ]);
        $this->useTenant($firstApprover, $requisition->company);
        app(ApproveProcurementDocumentAction::class)->handle($requisition, $firstApprover);
        $this->assertSame(PurchaseRequisitionStatus::Submitted, $requisition->refresh()->status);

        $secondApprover = User::factory()->create();
        $this->grant($secondApprover, $requisition->company, [
            'Approve:PurchaseRequisition',
            'ApproveLevelTwo:Procurement',
        ]);
        $this->useTenant($secondApprover, $requisition->company);
        app(ApproveProcurementDocumentAction::class)->handle($requisition, $secondApprover);

        $this->assertSame(PurchaseRequisitionStatus::Approved, $requisition->refresh()->status);
        $this->assertSame(
            [ProcurementApprovalStatus::Approved, ProcurementApprovalStatus::Approved],
            $requisition->approvalSteps->pluck('status')->all(),
        );
        $this->assertTrue(Activity::query()->where('log_name', 'purchase_requisitions')
            ->where('event', 'approval_step_approved')->where('subject_id', $requisition->getKey())->exists());
        $this->assertSame('0.0000', $line->refresh()->ordered_quantity);
    }

    public function test_submission_rejects_amount_above_remaining_approved_budget(): void
    {
        [$first, , $firstPreparer, $budgetLine] = $this->draftRequisition('60.0000', '10.0000', '1000.0000');
        $this->grant($firstPreparer, $first->company, ['Submit:PurchaseRequisition']);
        $this->useTenant($firstPreparer, $first->company);
        app(SubmitPurchaseRequisitionAction::class)->handle($first, $firstPreparer);

        $secondPreparer = User::factory()->create();
        $second = PurchaseRequisition::factory()->create([
            'company_id' => $first->company_id,
            'project_id' => $first->project_id,
            'project_site_id' => $first->project_site_id,
            'prepared_by_id' => $secondPreparer,
        ]);
        PurchaseRequisitionLine::factory()->create([
            'purchase_requisition_id' => $second,
            'company_id' => $second->company_id,
            'item_id' => $first->lines()->value('item_id'),
            'unit_of_measure_id' => $first->lines()->value('unit_of_measure_id'),
            'project_budget_line_id' => $budgetLine,
            'quantity' => '50.0000',
            'estimated_rate' => '10.0000',
        ]);
        $this->grant($secondPreparer, $second->company, ['Submit:PurchaseRequisition']);
        $this->useTenant($secondPreparer, $second->company);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('remaining approved budget');
        app(SubmitPurchaseRequisitionAction::class)->handle($second, $secondPreparer);
    }

    public function test_rejection_preserves_step_evidence_and_resubmission_creates_new_round(): void
    {
        [$requisition, , $preparer] = $this->draftRequisition();
        $this->grant($preparer, $requisition->company, ['Submit:PurchaseRequisition']);
        $this->useTenant($preparer, $requisition->company);
        app(SubmitPurchaseRequisitionAction::class)->handle($requisition, $preparer);
        $requisition->refresh();

        $reviewer = User::factory()->create();
        $this->grant($reviewer, $requisition->company, [
            'Approve:PurchaseRequisition',
            'Reject:PurchaseRequisition',
        ]);
        $this->useTenant($reviewer, $requisition->company);
        $this->assertTrue(Gate::forUser($reviewer)->allows('reject', $requisition));
        app(RejectProcurementDocumentAction::class)->handle($requisition, $reviewer, 'Revise specifications.');
        $this->assertSame(PurchaseRequisitionStatus::Rejected, $requisition->refresh()->status);

        $this->useTenant($preparer, $requisition->company);
        $this->assertTrue(Gate::forUser($preparer)->allows('submit', $requisition));
        app(SubmitPurchaseRequisitionAction::class)->handle($requisition, $preparer);

        $this->assertSame(2, $requisition->refresh()->approval_round);
        $this->assertCount(2, $requisition->approvalSteps);
        $this->assertSame(
            [ProcurementApprovalStatus::Rejected, ProcurementApprovalStatus::Pending],
            $requisition->approvalSteps->pluck('status')->all(),
        );
    }

    public function test_requisition_line_rejects_cross_company_item(): void
    {
        [$requisition] = $this->draftRequisition();
        $otherItem = Item::factory()->create(['company_id' => Company::factory()->create()]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('requisition company');
        PurchaseRequisitionLine::factory()->create([
            'purchase_requisition_id' => $requisition,
            'company_id' => $requisition->company_id,
            'line_number' => 2,
            'item_id' => $otherItem,
            'unit_of_measure_id' => $otherItem->unit_of_measure_id,
        ]);
    }

    public function test_multiple_lines_cannot_collectively_exceed_one_budget_line(): void
    {
        [$requisition, $firstLine, $preparer, $budgetLine] = $this->draftRequisition('60.0000', '10.0000', '1000.0000');
        PurchaseRequisitionLine::factory()->create([
            'purchase_requisition_id' => $requisition,
            'company_id' => $requisition->company_id,
            'line_number' => 2,
            'item_id' => $firstLine->item_id,
            'unit_of_measure_id' => $firstLine->unit_of_measure_id,
            'project_budget_line_id' => $budgetLine,
            'quantity' => '50.0000',
            'estimated_rate' => '10.0000',
        ]);
        $this->grant($preparer, $requisition->company, ['Submit:PurchaseRequisition']);
        $this->useTenant($preparer, $requisition->company);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('remaining approved budget');
        app(SubmitPurchaseRequisitionAction::class)->handle($requisition, $preparer);
    }

    /**
     * @return array{PurchaseRequisition, PurchaseRequisitionLine, User, ProjectBudgetLine}
     */
    private function draftRequisition(
        string $quantity = '10.0000',
        string $rate = '10.0000',
        string $budgetAmount = '1000.0000',
    ): array {
        $company = Company::factory()->create();
        $project = Project::factory()->create(['company_id' => $company]);
        $site = ProjectSite::factory()->create(['company_id' => $company, 'project_id' => $project]);
        $preparer = User::factory()->create();
        $budget = ProjectBudget::factory()->create([
            'company_id' => $company,
            'project_id' => $project,
            'prepared_by_id' => $preparer,
        ]);
        $budgetLine = ProjectBudgetLine::factory()->create([
            'company_id' => $company,
            'project_budget_id' => $budget,
            'amount' => $budgetAmount,
        ]);
        ProjectBudget::query()->whereKey($budget)->update([
            'status' => ProjectBudgetStatus::Approved,
            'total_amount' => $budgetAmount,
        ]);
        $item = Item::factory()->create(['company_id' => $company]);
        $requisition = PurchaseRequisition::factory()->create([
            'company_id' => $company,
            'project_id' => $project,
            'project_site_id' => $site,
            'prepared_by_id' => $preparer,
        ]);
        $line = PurchaseRequisitionLine::factory()->create([
            'purchase_requisition_id' => $requisition,
            'company_id' => $company,
            'item_id' => $item,
            'unit_of_measure_id' => $item->unit_of_measure_id,
            'project_budget_line_id' => $budgetLine,
            'quantity' => $quantity,
            'estimated_rate' => $rate,
        ]);

        return [$requisition, $line, $preparer, $budgetLine];
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function grant(User $user, Company $company, array $permissions): void
    {
        $user->companies()->syncWithoutDetaching([
            $company->getKey() => ['is_active' => true, 'can_access_descendants' => false],
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }

    private function useTenant(User $user, Company $company): void
    {
        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
