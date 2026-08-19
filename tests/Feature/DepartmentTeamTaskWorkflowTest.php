<?php

namespace Tests\Feature;

use App\Actions\DailyReports\SubmitDailyReportAction;
use App\Actions\Tasks\ApproveTaskByHeadAction;
use App\Actions\Tasks\RequestTaskRevisionAction;
use App\Actions\Tasks\ReviewTaskByLeadAction;
use App\Actions\Tasks\SubmitTaskAction;
use App\Enums\DailyReportSubmissionStatus;
use App\Enums\SocialContentType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamMemberRole;
use App\Enums\TeamType;
use App\Filament\Pages\DailyReportingMatrixPage;
use App\Filament\Pages\EmployeePerformancePage;
use App\Models\Company;
use App\Models\DailyWorkReport;
use App\Models\Department;
use App\Models\DepartmentTeam;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Task;
use App\Models\User;
use App\Services\CalculateEmployeeProductivityService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentTeamTaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_department_teams_and_members_hierarchy(): void
    {
        $company = Company::query()->firstOrFail();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create();
        $employment = Employment::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
        ]);

        $team = DepartmentTeam::create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'name' => 'Social Media Team',
            'code' => 'SMM',
            'team_type' => TeamType::SocialMedia,
            'team_lead_id' => $employment->id,
            'is_active' => true,
        ]);

        $team->members()->create([
            'employment_id' => $employment->id,
            'role_in_team' => TeamMemberRole::Lead,
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('department_teams', [
            'id' => $team->id,
            'name' => 'Social Media Team',
            'team_type' => 'social_media',
        ]);

        $this->assertDatabaseHas('department_team_members', [
            'department_team_id' => $team->id,
            'employment_id' => $employment->id,
            'role_in_team' => 'lead',
        ]);

        $this->assertTrue($department->teams()->where('id', $team->id)->exists());
        $this->assertTrue($employment->teamMemberships()->where('department_team_id', $team->id)->exists());
    }

    public function test_task_creation_and_atomic_code_sequence(): void
    {
        $company = Company::query()->firstOrFail();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create();
        $employment = Employment::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
        ]);

        $task = Task::create([
            'company_id' => $company->id,
            'title' => 'Create Facebook Ad Campaign',
            'department_id' => $department->id,
            'assigned_to_employment_id' => $employment->id,
            'priority' => TaskPriority::High,
            'deadline_date' => now()->addDays(2),
        ]);

        $year = now()->year;
        $this->assertStringStartsWith("TSK-{$year}-", $task->task_code);
        $this->assertEquals(TaskStatus::NotStarted, $task->status);
        $this->assertEquals(TaskPriority::High, $task->priority);
    }

    public function test_task_submission_and_two_tier_approval_chain(): void
    {
        $company = Company::query()->firstOrFail();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $employment = Employment::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
        ]);

        $leadUser = User::factory()->create();
        $headUser = User::factory()->create();

        $task = Task::create([
            'company_id' => $company->id,
            'title' => 'Design Brand Identity Guidelines',
            'department_id' => $department->id,
            'assigned_to_employment_id' => $employment->id,
            'deadline_date' => now()->addDays(3),
        ]);

        // Step 1: Member Submits
        app(SubmitTaskAction::class)->handle($task, $user, 'Initial designs ready on Google Drive.');
        $task->refresh();
        $this->assertEquals(TaskStatus::Submitted, $task->status);
        $this->assertEquals(100, $task->progress_percentage);
        $this->assertNotNull($task->submitted_at);

        // Step 2: Team Lead Level-1 Review
        app(ReviewTaskByLeadAction::class)->handle($task, $leadUser, 'Looks good, passing to Head.');
        $task->refresh();
        $this->assertEquals($leadUser->id, $task->lead_reviewed_by_id);
        $this->assertNotNull($task->lead_reviewed_at);

        // Step 3: Department Head Level-2 Final Approval
        app(ApproveTaskByHeadAction::class)->handle($task, $headUser, 'Approved. Excellent work.');
        $task->refresh();
        $this->assertEquals(TaskStatus::Completed, $task->status);
        $this->assertEquals($headUser->id, $task->head_approved_by_id);
        $this->assertNotNull($task->completed_at);
    }

    public function test_task_revision_workflow(): void
    {
        $company = Company::query()->firstOrFail();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create();
        $employment = Employment::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
        ]);
        $leadUser = User::factory()->create();

        $task = Task::create([
            'company_id' => $company->id,
            'title' => 'Edit Promo Video Reel',
            'department_id' => $department->id,
            'assigned_to_employment_id' => $employment->id,
            'status' => TaskStatus::Submitted,
            'deadline_date' => now()->addDay(),
        ]);

        app(RequestTaskRevisionAction::class)->handle($task, $leadUser, 'Please reduce duration to 30s and change background music.');
        $task->refresh();

        $this->assertEquals(TaskStatus::RevisionRequired, $task->status);
        $this->assertEquals(1, $task->revision_count);
        $this->assertDatabaseHas('task_revisions', [
            'task_id' => $task->id,
            'revision_number' => 1,
            'requested_by_user_id' => $leadUser->id,
        ]);
    }

    public function test_specialized_task_details_extensions(): void
    {
        $company = Company::query()->firstOrFail();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create();
        $employment = Employment::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
        ]);

        $task = Task::create([
            'company_id' => $company->id,
            'title' => 'Instagram Carousel Campaign',
            'department_id' => $department->id,
            'assigned_to_employment_id' => $employment->id,
            'deadline_date' => now()->addDays(1),
        ]);

        $task->socialMediaDetail()->create([
            'client_brand' => 'YM Construction',
            'platforms' => ['instagram', 'facebook'],
            'content_type' => SocialContentType::Carousel,
            'caption' => 'Check out our new luxury villas project!',
            'hashtags_keywords' => '#construction #villas',
        ]);

        $this->assertDatabaseHas('social_media_task_details', [
            'task_id' => $task->id,
            'client_brand' => 'YM Construction',
            'content_type' => 'carousel',
        ]);

        $this->assertNotNull($task->socialMediaDetail);
        $this->assertEquals('YM Construction', $task->socialMediaDetail->client_brand);
    }

    public function test_daily_work_report_on_time_submission_and_task_aggregation(): void
    {
        $company = Company::query()->firstOrFail();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create();
        $employment = Employment::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
        ]);

        $task = Task::create([
            'company_id' => $company->id,
            'title' => 'Landing Page Hero Section',
            'department_id' => $department->id,
            'assigned_to_employment_id' => $employment->id,
            'deadline_date' => now(),
        ]);

        // Simulate 2:00 PM submission (On-Time <= 6:00 PM)
        Carbon::setTestNow(now()->setTime(14, 0, 0));

        $action = app(SubmitDailyReportAction::class);
        $report = $action->handle(
            $employment,
            [
                'report_date' => now()->toDateString(),
                'blockers_summary' => 'Waiting for design tokens',
                'additional_comments' => 'Completed coding responsive layout.',
            ],
            [
                [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'status_today' => 'completed',
                    'hours_spent' => 4.5,
                    'progress_percentage' => 100,
                    'deliverable_summary' => 'Hero section completed with mobile responsiveness.',
                    'work_links' => 'https://github.com/project/pull/12',
                ],
                [
                    'task_id' => null,
                    'task_title' => 'Code Review & Team Sync',
                    'status_today' => 'completed',
                    'hours_spent' => 1.5,
                    'progress_percentage' => 100,
                    'deliverable_summary' => 'Reviewed PRs',
                ],
            ]
        );

        $this->assertEquals(DailyReportSubmissionStatus::OnTime, $report->submission_status);
        $this->assertEquals(2, $report->tasks_completed_count);
        $this->assertEquals(100, $report->overall_progress_percentage);
        $this->assertEquals(2, $report->deliverables_count);
        $this->assertCount(2, $report->taskItems);

        Carbon::setTestNow(); // Reset time
    }

    public function test_calculate_employee_productivity_service(): void
    {
        $company = Company::query()->firstOrFail();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create(['full_name' => 'Ahmed Khan']);
        $employment = Employment::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
        ]);

        // Create 2 completed tasks and 1 overdue task
        Task::create([
            'company_id' => $company->id,
            'title' => 'Task 1',
            'department_id' => $department->id,
            'assigned_to_employment_id' => $employment->id,
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
            'deadline_date' => now()->addDay(),
            'revision_count' => 0,
        ]);

        Task::create([
            'company_id' => $company->id,
            'title' => 'Task 2',
            'department_id' => $department->id,
            'assigned_to_employment_id' => $employment->id,
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
            'deadline_date' => now()->addDay(),
            'revision_count' => 1,
        ]);

        Task::create([
            'company_id' => $company->id,
            'title' => 'Task 3 (Overdue)',
            'department_id' => $department->id,
            'assigned_to_employment_id' => $employment->id,
            'status' => TaskStatus::InProgress,
            'deadline_date' => now()->subDay(),
        ]);

        // Create on-time daily report
        DailyWorkReport::create([
            'company_id' => $company->id,
            'employment_id' => $employment->id,
            'department_id' => $department->id,
            'report_date' => now()->toDateString(),
            'submitted_at' => now(),
            'submission_status' => DailyReportSubmissionStatus::OnTime,
            'tasks_completed_count' => 2,
            'overall_progress_percentage' => 100,
        ]);

        $service = app(CalculateEmployeeProductivityService::class);
        $metrics = $service->calculate($employment, now()->startOfMonth(), now()->endOfDay());

        $this->assertEquals(3, $metrics['tasks_assigned']);
        $this->assertEquals(2, $metrics['tasks_completed']);
        $this->assertEquals(1, $metrics['tasks_overdue']);
        $this->assertEquals(66.7, $metrics['task_completion_rate']);
        $this->assertEquals(1, $metrics['total_revisions']);
        $this->assertEquals(1, $metrics['reports_submitted_on_time']);
        $this->assertGreaterThan(0, $metrics['productivity_score']);
    }

    public function test_check_daily_report_deadline_command_finds_missing_staff(): void
    {
        $this->artisan('work-reports:check-deadline')
            ->assertExitCode(0);
    }

    public function test_daily_reporting_matrix_page_renders_successfully(): void
    {
        $superAdmin = User::query()->where('email', 'superadmin@gmail.com')->firstOrFail();
        $company = Company::query()->firstOrFail();

        $this->actingAs($superAdmin);
        Filament::setTenant($company);

        Livewire::test(DailyReportingMatrixPage::class)
            ->assertSuccessful();
    }

    public function test_employee_performance_page_renders_successfully(): void
    {
        $superAdmin = User::query()->where('email', 'superadmin@gmail.com')->firstOrFail();
        $company = Company::query()->firstOrFail();

        $this->actingAs($superAdmin);
        Filament::setTenant($company);

        Livewire::test(EmployeePerformancePage::class)
            ->assertSuccessful();
    }
}
