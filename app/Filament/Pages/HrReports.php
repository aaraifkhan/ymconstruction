<?php

namespace App\Filament\Pages;

use App\Reports\CompanyHrReport;
use App\Reports\HrReportCsvExporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'HR Reports & Dashboard';

    protected string $view = 'filament.pages.hr-reports';

    /** @var array<string, int> */
    public array $dashboard = [];

    /** @var array<int, array<string, mixed>> */
    public array $employees = [];

    /** @var array<int, array<string, mixed>> */
    public array $departments = [];

    /** @var array<int, array<string, mixed>> */
    public array $designations = [];

    /** @var array<int, array<string, mixed>> */
    public array $financing = [];

    /** @var array<int, array<string, mixed>> */
    public array $increments = [];

    /** @var array<int, array<string, mixed>> */
    public array $attendance = [];

    /** @var array<int, array<string, mixed>> */
    public array $leave = [];

    public bool $canViewFinancing = false;

    public bool $canViewIncrements = false;

    public bool $canViewAttendance = false;

    public bool $canViewLeave = false;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:HrReports'));
    }

    public function mount(CompanyHrReport $report): void
    {
        abort_unless(static::canAccess(), 403);
        $result = $report->forCompany(Filament::auth()->user(), Filament::getTenant());

        foreach (['dashboard', 'employees', 'departments', 'designations', 'financing', 'increments', 'attendance', 'leave'] as $key) {
            $this->{$key} = $result[$key] instanceof Collection ? $result[$key]->all() : $result[$key];
        }

        $this->canViewFinancing = $result['can_view_financing'];
        $this->canViewIncrements = $result['can_view_increments'];
        $this->canViewAttendance = $result['can_view_attendance'];
        $this->canViewLeave = $result['can_view_leave'];
    }

    public function export(string $report, HrReportCsvExporter $exporter, string $format = 'csv'): StreamedResponse
    {
        $user = Filament::auth()->user();
        abort_unless(static::canAccess() && ($user->hasRole('super_admin') || $user->can('Export:HrReports')), 403);

        [$rows, $columns] = match ($report) {
            'employee-list' => [$this->employees, $this->employeeColumns()],
            'department-employees' => [$this->departments, ['department' => 'Department', ...$this->employeeColumns()]],
            'designation-employees' => [$this->designations, ['designation' => 'Designation', ...$this->employeeColumns()]],
            'employee-loans' => [$this->authorizedFinancingRows('Loan'), $this->financingColumns()],
            'employee-advances' => [$this->authorizedFinancingRows('Advance'), $this->financingColumns()],
            'increment-history' => [$this->authorizedRows($this->canViewIncrements, $this->increments), [
                'employee_code' => 'Employee Code', 'employee_name' => 'Employee',
                'effective_from' => 'Effective From', 'effective_to' => 'Effective To',
                'previous_gross' => 'Previous Gross', 'new_gross' => 'New Gross', 'increment' => 'Increment',
            ]],
            'attendance-summary' => [$this->authorizedRows($this->canViewAttendance, $this->attendance), [
                'period' => 'Period', 'employee_code' => 'Employee Code', 'employee_name' => 'Employee',
                'status' => 'Status', 'scheduled_days' => 'Scheduled Days', 'present_days' => 'Present Days',
                'absent_days' => 'Absent Days', 'half_days' => 'Half Days', 'leave_days' => 'Leave Days',
                'late_minutes' => 'Late Minutes', 'overtime_minutes' => 'Overtime Minutes',
            ]],
            'leave-summary' => [$this->authorizedRows($this->canViewLeave, $this->leave), [
                'employee_code' => 'Employee Code', 'employee_name' => 'Employee', 'leave_type' => 'Leave Type',
                'dates' => 'Dates', 'requested_units' => 'Requested Units', 'status' => 'Status',
                'paid' => 'Paid', 'current_balance' => 'Current Balance',
            ]],
            default => abort(404),
        };

        return $exporter->download($user, Filament::getTenant(), $report, collect($rows), $columns, format: $format);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make($this->exportActions('csv'))->label('Export CSV')
                ->visible(fn (): bool => Filament::auth()->user()?->hasRole('super_admin')
                    || Filament::auth()->user()?->can('Export:HrReports')),
            ActionGroup::make($this->exportActions('xlsx'))->label('Export Excel')
                ->visible(fn (): bool => Filament::auth()->user()?->hasRole('super_admin')
                    || Filament::auth()->user()?->can('Export:HrReports')),
        ];
    }

    /** @return array<int, Action> */
    private function exportActions(string $format): array
    {
        $suffix = str($format)->headline()->toString();

        return [
            Action::make("exportEmployees{$suffix}")->label('Employee List')->action(fn () => $this->export('employee-list', app(HrReportCsvExporter::class), $format)),
            Action::make("exportDepartments{$suffix}")->label('Department-wise')->action(fn () => $this->export('department-employees', app(HrReportCsvExporter::class), $format)),
            Action::make("exportDesignations{$suffix}")->label('Designation-wise')->action(fn () => $this->export('designation-employees', app(HrReportCsvExporter::class), $format)),
            Action::make("exportLoans{$suffix}")->label('Employee Loans')->visible(fn (): bool => $this->canViewFinancing)->action(fn () => $this->export('employee-loans', app(HrReportCsvExporter::class), $format)),
            Action::make("exportAdvances{$suffix}")->label('Employee Advances')->visible(fn (): bool => $this->canViewFinancing)->action(fn () => $this->export('employee-advances', app(HrReportCsvExporter::class), $format)),
            Action::make("exportIncrements{$suffix}")->label('Increment History')->visible(fn (): bool => $this->canViewIncrements)->action(fn () => $this->export('increment-history', app(HrReportCsvExporter::class), $format)),
            Action::make("exportAttendance{$suffix}")->label('Attendance Summary')->visible(fn (): bool => $this->canViewAttendance)->action(fn () => $this->export('attendance-summary', app(HrReportCsvExporter::class), $format)),
            Action::make("exportLeave{$suffix}")->label('Leave Summary')->visible(fn (): bool => $this->canViewLeave)->action(fn () => $this->export('leave-summary', app(HrReportCsvExporter::class), $format)),
        ];
    }

    /** @return array<string, string> */
    private function employeeColumns(): array
    {
        return [
            'employee_code' => 'Employee Code',
            'employee_name' => 'Employee',
            'department' => 'Department',
            'designation' => 'Designation',
            'employment_type' => 'Employment Type',
            'employment_status' => 'Status',
            'work_location' => 'Work Location',
            'joining_date' => 'Joining Date',
            'ending_date' => 'Ending Date',
        ];
    }

    /** @return array<string, string> */
    private function financingColumns(): array
    {
        return [
            'reference_number' => 'Reference', 'employee_code' => 'Employee Code',
            'employee_name' => 'Employee', 'request_date' => 'Request Date', 'status' => 'Status',
            'principal' => 'Principal', 'total_repayable' => 'Total Repayable', 'outstanding' => 'Outstanding',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function authorizedRows(bool $authorized, array $rows): array
    {
        abort_unless($authorized, 403);

        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    private function authorizedFinancingRows(string $type): array
    {
        return collect($this->authorizedRows($this->canViewFinancing, $this->financing))
            ->where('type', $type)
            ->values()
            ->all();
    }
}
