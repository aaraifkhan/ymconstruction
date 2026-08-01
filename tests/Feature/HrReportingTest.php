<?php

namespace Tests\Feature;

use App\Filament\Pages\FinalSettlementReports;
use App\Filament\Pages\GroupHrReports;
use App\Filament\Pages\HrReports;
use App\Filament\Pages\PayrollReports;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\User;
use App\Reports\CompanyHrReport;
use App\Reports\GroupHrReport;
use App\Reports\HrReportCsvExporter;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HrReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_company_reports_are_tenant_scoped_exclude_protected_identity_and_use_bounded_queries(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company]);
        $designation = Designation::factory()->create(['company_id' => $company]);
        Employment::factory()->count(100)->forCompany($company)->create([
            'department_id' => $department,
            'designation_id' => $designation,
            'joining_date' => today()->startOfMonth(),
        ]);
        Employment::factory()->forCompany($otherCompany)->create(['employee_code' => 'OTHER-001']);

        $user = User::factory()->create();
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $this->grant($user, [
            'View:HrReports',
            'ViewAny:EmployeeFinancing',
            'ViewAmounts:EmploymentCompensation',
            'ViewAny:AttendanceMonthlySummary',
            'ViewAny:LeaveRequest',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $report = app(CompanyHrReport::class)->forCompany($user, $company);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(100, $report['employees']);
        $this->assertSame(100, $report['dashboard']['unique_people']);
        $this->assertSame(100, $report['dashboard']['employment_count']);
        $this->assertSame(100, $report['dashboard']['joiners_this_month']);
        $this->assertLessThanOrEqual(20, $queryCount);
        $this->assertArrayNotHasKey('cnic', $report['employees']->first());
        $this->assertArrayNotHasKey('bank_account', $report['employees']->first());
        $this->assertFalse($report['employees']->contains('employee_code', 'OTHER-001'));
    }

    public function test_collective_report_uses_all_authorized_companies_and_explicit_people_vs_employment_semantics(): void
    {
        $root = Company::factory()->create(['name' => '7 Orbit']);
        $child = Company::factory()->create(['name' => '7 Orbit Medical Billing']);
        $unrelated = Company::factory()->create(['name' => 'YMC Construction']);
        $employee = Employee::factory()->create();
        Employment::factory()->forCompany($root)->create([
            'employee_id' => $employee,
            'employee_code' => 'ROOT-001',
            'joining_date' => '2026-01-01',
        ]);
        Employment::factory()->forCompany($child)->create([
            'employee_id' => $employee,
            'employee_code' => 'CHILD-001',
            'joining_date' => '2026-02-01',
        ]);
        Employment::factory()->forCompany($unrelated)->create(['employee_code' => 'OTHER-001']);

        $user = User::factory()->create();
        $user->companies()->attach([
            $root->getKey() => ['is_active' => true, 'can_access_descendants' => false],
            $child->getKey() => ['is_active' => true, 'can_access_descendants' => false],
            $unrelated->getKey() => ['is_active' => true, 'can_access_descendants' => false],
        ]);
        $this->grant($user, ['View:GroupHrReports']);

        $report = app(GroupHrReport::class)->forGroup(
            $user,
            $root,
            CarbonImmutable::parse('2026-01-01'),
            CarbonImmutable::parse('2026-12-31'),
        );

        $this->assertSame(2, $report['unique_people']);
        $this->assertSame(3, $report['employment_count']);
        $this->assertCount(3, $report['rows']);
        $this->assertTrue($report['rows']->contains('company', 'YMC Construction'));

        $restricted = User::factory()->create();
        $restricted->companies()->attach($root, ['is_active' => true, 'can_access_descendants' => false]);
        $this->grant($restricted, ['View:GroupHrReports']);

        $this->expectException(ValidationException::class);
        app(GroupHrReport::class)->forGroup(
            $restricted,
            $root,
            CarbonImmutable::parse('2026-01-01'),
            CarbonImmutable::parse('2026-12-31'),
        );
    }

    public function test_authorized_pages_render_and_private_csv_export_is_audited(): void
    {
        $root = Company::factory()->create(['name' => '7-Orbit', 'slug' => '7-orbit']);
        Company::factory()->create(['name' => '7-Orbit IT', 'parent_company_id' => $root]);
        $employment = Employment::factory()->forCompany($root)->create(['employee_code' => 'EMP-00001']);
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));
        $this->actingAs($user);
        Filament::setTenant($root);
        Filament::bootCurrentPanel();

        Livewire::test(HrReports::class)
            ->assertSee('EMP-00001')
            ->assertSuccessful();
        Livewire::test(GroupHrReports::class)
            ->assertSee('Unique people across group')
            ->assertSuccessful();

        $response = app(HrReportCsvExporter::class)->download(
            $user,
            $root,
            'employee-list',
            collect([['employee_code' => $employment->employee_code]]),
            ['employee_code' => 'Employee Code'],
        );
        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        $this->assertStringContainsString('Employee Code', $content);
        $this->assertStringContainsString('EMP-00001', $content);
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $xlsxResponse = app(HrReportCsvExporter::class)->download(
            $user,
            $root,
            'employee-list',
            collect([['employee_code' => $employment->employee_code]]),
            ['employee_code' => 'Employee Code'],
            format: 'xlsx',
        );
        ob_start();
        $xlsxResponse->sendContent();
        $xlsxContent = (string) ob_get_clean();
        $this->assertStringStartsWith('PK', $xlsxContent);
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $xlsxResponse->headers->get('Content-Type'),
        );

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'hr_report_exports',
            'event' => 'exported',
            'subject_type' => Company::class,
            'subject_id' => $root->getKey(),
            'causer_id' => $user->getKey(),
        ]);
    }

    public function test_sensitive_report_sections_require_their_existing_amount_permissions(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        $this->grant($user, [
            'View:HrReports',
            'View:PayrollReports',
            'View:FinalSettlementReport',
            'View:GroupHrReports',
        ]);
        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        $companyReport = app(CompanyHrReport::class)->forCompany($user, $company);
        $groupReport = app(GroupHrReport::class)->forGroup(
            $user,
            $company,
            CarbonImmutable::parse('2026-01-01'),
            CarbonImmutable::parse('2026-12-31'),
        );

        $this->assertFalse($companyReport['can_view_financing']);
        $this->assertFalse($companyReport['can_view_increments']);
        $this->assertFalse(PayrollReports::canAccess());
        $this->assertFalse(FinalSettlementReports::canAccess());
        $this->assertFalse($groupReport['payroll_visible']);
        $this->assertFalse($groupReport['financing_visible']);
        $this->assertNull($groupReport['rows']->sole()['payroll_cost']);
        $this->assertNull($groupReport['rows']->sole()['loan_outstanding']);
    }

    /** @param array<int, string> $permissions */
    private function grant(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }
}
