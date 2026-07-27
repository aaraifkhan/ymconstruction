<?php

namespace Tests\Feature;

use App\Actions\Compensation\ApproveEmploymentCompensationAction;
use App\Actions\Compensation\RejectEmploymentCompensationAction;
use App\Actions\Compensation\SubmitEmploymentCompensationAction;
use App\Enums\CompensationStatus;
use App\Models\Company;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmploymentCompensationWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_amounts_are_encrypted_and_gross_salary_is_derived(): void
    {
        [$compensation] = $this->draftCompensation();
        $table = (new EmploymentCompensation)->getTable();
        $rawBasicSalary = DB::table($table)->where('id', $compensation->getKey())->value('basic_salary');

        $this->assertNotSame('100000.00', $rawBasicSalary);
        $this->assertSame('100000.00', $compensation->basic_salary);
        $this->assertSame(125000.0, $compensation->grossSalary());
    }

    public function test_compensation_follows_submit_approve_and_reject_workflow(): void
    {
        [$compensation, $user] = $this->draftCompensation();

        app(SubmitEmploymentCompensationAction::class)->handle($compensation, $user);
        $this->assertSame(CompensationStatus::PendingApproval, $compensation->refresh()->status);

        app(ApproveEmploymentCompensationAction::class)->handle($compensation, $user);
        $compensation->refresh();

        $this->assertSame(CompensationStatus::Approved, $compensation->status);
        $this->assertSame($user->getKey(), $compensation->approved_by_id);
        $this->assertNotNull($compensation->approved_at);

        $rejected = EmploymentCompensation::factory()->create([
            'company_id' => $compensation->company_id,
            'employment_id' => $compensation->employment_id,
            'effective_from' => today()->addYear(),
            'created_by_id' => $user->getKey(),
        ]);
        app(SubmitEmploymentCompensationAction::class)->handle($rejected, $user);
        app(RejectEmploymentCompensationAction::class)->handle($rejected, $user, 'Allowance needs correction.');

        $this->assertSame(CompensationStatus::Rejected, $rejected->refresh()->status);
        $this->assertSame('Allowance needs correction.', $rejected->rejection_reason);
    }

    public function test_approving_new_compensation_closes_previous_active_period(): void
    {
        [$previous, $user] = $this->draftCompensation([
            'effective_from' => '2026-01-01',
        ]);
        app(SubmitEmploymentCompensationAction::class)->handle($previous, $user);
        app(ApproveEmploymentCompensationAction::class)->handle($previous, $user);

        $next = EmploymentCompensation::factory()->create([
            'company_id' => $previous->company_id,
            'employment_id' => $previous->employment_id,
            'effective_from' => '2026-07-01',
            'basic_salary' => '120000.00',
            'created_by_id' => $user->getKey(),
        ]);
        app(SubmitEmploymentCompensationAction::class)->handle($next, $user);
        app(ApproveEmploymentCompensationAction::class)->handle($next, $user);

        $this->assertSame('2026-06-30', $previous->refresh()->effective_to->toDateString());
        $this->assertSame(CompensationStatus::Approved, $next->refresh()->status);
        $this->assertTrue(
            EmploymentCompensation::query()
                ->approved()
                ->effectiveOn('2026-07-01')
                ->sole()
                ->is($next),
        );
    }

    public function test_approval_rejects_overlap_with_future_approved_period(): void
    {
        [$future, $user] = $this->draftCompensation([
            'effective_from' => '2026-07-01',
        ]);
        app(SubmitEmploymentCompensationAction::class)->handle($future, $user);
        app(ApproveEmploymentCompensationAction::class)->handle($future, $user);

        $overlap = EmploymentCompensation::factory()->create([
            'company_id' => $future->company_id,
            'employment_id' => $future->employment_id,
            'effective_from' => '2026-06-01',
            'effective_to' => '2026-12-31',
            'created_by_id' => $user->getKey(),
        ]);
        app(SubmitEmploymentCompensationAction::class)->handle($overlap, $user);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('overlaps approved compensation');

        app(ApproveEmploymentCompensationAction::class)->handle($overlap, $user);
    }

    public function test_approved_salary_values_are_immutable(): void
    {
        [$compensation, $user] = $this->draftCompensation();
        app(SubmitEmploymentCompensationAction::class)->handle($compensation, $user);
        app(ApproveEmploymentCompensationAction::class)->handle($compensation, $user);

        $this->expectException(ValidationException::class);

        $compensation->refresh()->update(['basic_salary' => '200000.00']);
    }

    public function test_cross_company_employment_and_invalid_dates_are_rejected(): void
    {
        $company = Company::factory()->create();
        $otherEmployment = Employment::factory()->create();

        try {
            EmploymentCompensation::factory()->create([
                'company_id' => $company->getKey(),
                'employment_id' => $otherEmployment->getKey(),
            ]);
            $this->fail('Cross-company employment should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('employment_id', $exception->errors());
        }

        $employment = Employment::factory()->forCompany($company)->create();

        $this->expectException(ValidationException::class);

        EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'effective_from' => '2026-07-01',
            'effective_to' => '2026-06-30',
        ]);
    }

    public function test_salary_amounts_and_notes_are_excluded_from_activity_log(): void
    {
        [$compensation, $user] = $this->draftCompensation([
            'notes' => 'Confidential owner-approved package.',
        ]);
        app(SubmitEmploymentCompensationAction::class)->handle($compensation, $user);

        $activityPayload = Activity::query()
            ->where('subject_type', EmploymentCompensation::class)
            ->get()
            ->pluck('properties')
            ->map(fn ($properties): string => json_encode($properties))
            ->implode(' ');

        $this->assertStringNotContainsString('100000', $activityPayload);
        $this->assertStringNotContainsString('Confidential owner-approved package', $activityPayload);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{EmploymentCompensation, User}
     */
    private function draftCompensation(array $overrides = []): array
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'Submit:EmploymentCompensation',
            'Approve:EmploymentCompensation',
            'Reject:EmploymentCompensation',
        ]);

        $compensation = EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'created_by_id' => $user->getKey(),
            ...$overrides,
        ]);

        return [$compensation, $user];
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function authenticateCompanyUser(User $user, Company $company, array $permissions): void
    {
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
