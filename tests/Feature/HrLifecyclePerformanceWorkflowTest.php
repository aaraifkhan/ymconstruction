<?php

namespace Tests\Feature;

use App\Actions\HR\TransitionAppraisalCycleAction;
use App\Actions\HR\TransitionEmployeeWarningAction;
use App\Actions\HR\TransitionEmploymentMovementAction;
use App\Actions\HR\TransitionEmploymentSeparationAction;
use App\Actions\HR\TransitionPerformanceAppraisalAction;
use App\Enums\EmployeeWarningStatus;
use App\Enums\EmploymentAccessReviewStatus;
use App\Enums\EmploymentMovementStatus;
use App\Enums\EmploymentSeparationStatus;
use App\Enums\EmploymentSeparationType;
use App\Enums\EmploymentStatus;
use App\Enums\PerformanceAppraisalStatus;
use App\Filament\Resources\EmployeeWarnings\Pages\ListEmployeeWarnings;
use App\Filament\Resources\EmploymentMovementRequests\Pages\ListEmploymentMovementRequests;
use App\Filament\Resources\EmploymentSeparations\Pages\ListEmploymentSeparations;
use App\Filament\Resources\PerformanceAppraisals\Pages\ListPerformanceAppraisals;
use App\Models\AppraisalCycle;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeWarning;
use App\Models\Employment;
use App\Models\EmploymentMovementRequest;
use App\Models\EmploymentSeparation;
use App\Models\PerformanceAppraisal;
use App\Models\PerformanceAppraisalItem;
use App\Models\PerformanceKpi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HrLifecyclePerformanceWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_appraisal_uses_configured_scale_weighted_score_and_three_independent_actors(): void
    {
        [$company, $maker, $reviewer, $approver] = $this->context();
        $employeeEmployment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        $reviewerEmployment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        $cycle = AppraisalCycle::factory()->create([
            'company_id' => $company,
            'score_min' => 1,
            'score_max' => 10,
        ]);
        app(TransitionAppraisalCycleAction::class)->activate($cycle, $maker);
        $appraisal = PerformanceAppraisal::factory()->create([
            'company_id' => $company,
            'appraisal_cycle_id' => $cycle,
            'employment_id' => $employeeEmployment,
            'reviewer_employment_id' => $reviewerEmployment,
            'created_by_id' => $maker,
        ]);
        $first = $this->appraisalItem($appraisal, $company, 40);
        $second = $this->appraisalItem($appraisal, $company, 60);

        $workflow = app(TransitionPerformanceAppraisalAction::class);
        $workflow->submit($appraisal, $maker);
        $this->expectValidationException(fn () => $workflow->review($appraisal->refresh(), [
            $first->getKey() => ['score' => 8],
            $second->getKey() => ['score' => 6],
        ], 'Strong performance.', $maker));
        $workflow->review($appraisal->refresh(), [
            $first->getKey() => ['score' => 8, 'comments' => 'Exceeded target.'],
            $second->getKey() => ['score' => 6, 'comments' => 'Met target.'],
        ], 'Strong performance.', $reviewer);
        $workflow->approve($appraisal->refresh(), $approver);
        $workflow->acknowledge($appraisal->refresh(), 'Discussed and acknowledged.', $maker);

        $appraisal->refresh();
        $this->assertSame(PerformanceAppraisalStatus::Acknowledged, $appraisal->status);
        $this->assertSame('6.8000', $appraisal->overall_score);
        $this->assertNotNull($appraisal->source_checksum);
        $this->assertSame('8.0000', $first->refresh()->score);
        $this->expectValidationException(fn () => $first->update(['weight' => 10]));
    }

    public function test_warning_workflow_preserves_issued_evidence_and_audit_history(): void
    {
        [$company, $maker, $issuer] = $this->context();
        $employment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        $warning = EmployeeWarning::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'reference_number' => null,
            'created_by_id' => $maker,
        ]);
        $workflow = app(TransitionEmployeeWarningAction::class);

        $this->expectValidationException(fn () => $workflow->issue($warning, $maker));
        $workflow->issue($warning->refresh(), $issuer);
        $workflow->respond($warning->refresh(), 'Employee response.', $maker);
        $workflow->acknowledge($warning->refresh(), $maker);
        $workflow->close($warning->refresh(), 'Corrective action completed.', $issuer);

        $warning->refresh();
        $this->assertSame(EmployeeWarningStatus::Closed, $warning->status);
        $this->assertSame('WRN-'.sprintf('%06d', $warning->getKey()), $warning->reference_number);
        $this->assertGreaterThanOrEqual(4, Activity::query()
            ->where('subject_type', EmployeeWarning::class)
            ->where('subject_id', $warning->getKey())
            ->count());
        $this->expectValidationException(fn () => $warning->update(['subject' => 'Changed evidence']));
    }

    public function test_due_transfer_applies_once_and_records_effective_dated_history(): void
    {
        [$company, $maker, $approver] = $this->context();
        $oldDepartment = Department::factory()->for($company)->create();
        $newDepartment = Department::factory()->for($company)->create();
        $employment = Employment::factory()->forCompany($company)->create([
            'joining_date' => '2026-01-01',
            'department_id' => $oldDepartment,
        ]);
        $movement = EmploymentMovementRequest::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'reference_number' => null,
            'effective_on' => '2026-07-15',
            'target_department_id' => $newDepartment,
            'created_by_id' => $maker,
        ]);
        $workflow = app(TransitionEmploymentMovementAction::class);
        $workflow->submit($movement, $maker);
        $workflow->approve($movement->refresh(), $approver);

        $movement->refresh();
        $this->assertSame(EmploymentMovementStatus::Applied, $movement->status);
        $this->assertTrue($employment->refresh()->department->is($newDepartment));
        $change = $employment->changes()->where('event_type', 'transfer')->sole();
        $this->assertSame('2026-07-15', $change->effective_on->toDateString());
        $this->assertSame($oldDepartment->getKey(), $change->before_snapshot['department_id']);
        $this->assertSame($newDepartment->getKey(), $change->after_snapshot['department_id']);
        $workflow->apply($movement->refresh(), $approver);
        $this->assertSame(1, $employment->changes()->where('event_type', 'transfer')->count());
    }

    public function test_resignation_approval_updates_employment_and_creates_explicit_access_review(): void
    {
        [$company, $maker, $acceptor, $approver] = $this->context();
        $linkedUser = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $linkedUser]);
        $employment = Employment::factory()->forCompany($company)->create([
            'employee_id' => $employee,
            'joining_date' => '2026-01-01',
        ]);
        $separation = EmploymentSeparation::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'reference_number' => null,
            'request_date' => '2026-07-01',
            'proposed_last_working_date' => '2026-07-31',
            'created_by_id' => $maker,
        ]);
        $workflow = app(TransitionEmploymentSeparationAction::class);
        $workflow->submit($separation, $maker);
        $workflow->acceptResignation($separation->refresh(), $acceptor);
        $this->expectValidationException(fn () => $workflow->approve(
            $separation->refresh(), CarbonImmutable::parse('2026-07-31'), $acceptor,
        ));
        $workflow->approve($separation->refresh(), CarbonImmutable::parse('2026-07-31'), $approver);

        $separation->refresh();
        $employment->refresh();
        $this->assertSame(EmploymentSeparationStatus::Approved, $separation->status);
        $this->assertSame(EmploymentAccessReviewStatus::Pending, $separation->access_review_status);
        $this->assertSame(EmploymentStatus::Resigned, $employment->employment_status);
        $this->assertSame('2026-07-31', $employment->ending_date->toDateString());
        $this->assertFalse($linkedUser->refresh()->trashed());
        $workflow->completeAccessReview($separation, $approver);
        $this->assertSame(EmploymentAccessReviewStatus::Completed, $separation->refresh()->access_review_status);
    }

    public function test_company_boundaries_termination_authority_and_tenant_resources_are_enforced(): void
    {
        [$company, $maker] = $this->context();
        $otherCompany = Company::factory()->create();
        $otherEmployment = Employment::factory()->forCompany($otherCompany)->create(['joining_date' => '2026-01-01']);
        $this->expectValidationException(fn () => EmploymentSeparation::factory()->create([
            'company_id' => $company,
            'employment_id' => $otherEmployment,
        ]));
        $employment = Employment::factory()->forCompany($company)->create(['joining_date' => '2026-01-01']);
        $this->expectValidationException(fn () => EmploymentSeparation::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'type' => EmploymentSeparationType::Termination,
            'authority' => null,
        ]));

        $appraisal = PerformanceAppraisal::factory()->create([
            'company_id' => $company,
            'employment_id' => $employment,
            'reviewer_employment_id' => Employment::factory()->forCompany($company)->create(),
        ]);
        $warning = EmployeeWarning::factory()->create(['company_id' => $company, 'employment_id' => $employment]);
        $movement = EmploymentMovementRequest::factory()->create(['company_id' => $company, 'employment_id' => $employment]);
        $separation = EmploymentSeparation::factory()->create(['company_id' => $company, 'employment_id' => $employment]);

        $this->actingAs($maker);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
        Livewire::test(ListPerformanceAppraisals::class)->assertCanSeeTableRecords([$appraisal])->assertSuccessful();
        Livewire::test(ListEmployeeWarnings::class)->assertCanSeeTableRecords([$warning])->assertSuccessful();
        Livewire::test(ListEmploymentMovementRequests::class)->assertCanSeeTableRecords([$movement])->assertSuccessful();
        Livewire::test(ListEmploymentSeparations::class)->assertCanSeeTableRecords([$separation])->assertSuccessful();
    }

    /** @return array{Company, User, User, User} */
    private function context(): array
    {
        $company = Company::factory()->create();
        $role = Role::findOrCreate('super_admin');
        [$first, $second, $third] = User::factory()->count(3)->create()->each->assignRole($role)->all();

        return [$company, $first, $second, $third];
    }

    private function appraisalItem(PerformanceAppraisal $appraisal, Company $company, int $weight): PerformanceAppraisalItem
    {
        return PerformanceAppraisalItem::factory()->create([
            'company_id' => $company,
            'performance_appraisal_id' => $appraisal,
            'performance_kpi_id' => PerformanceKpi::factory()->create(['company_id' => $company]),
            'weight' => $weight,
        ]);
    }

    private function expectValidationException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected validation exception.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }
}
