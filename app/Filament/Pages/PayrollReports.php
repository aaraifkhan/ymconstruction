<?php

namespace App\Filament\Pages;

use App\Reports\EmployeeAdvanceLedgerReport;
use App\Reports\HrReportCsvExporter;
use App\Reports\PayrollReconciliationReport;
use App\Reports\PayrollSummaryReport;
use App\Reports\ProjectPayrollReport;
use App\Reports\SalaryRegisterReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Payroll & Advances';

    protected string $view = 'filament.pages.payroll-reports';

    /** @var array<int, array<string, mixed>> */
    public array $reconciliationRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $advanceRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $salaryRegisterRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $summaryRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $projectRows = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null && $user !== null
            && ($user->hasRole('super_admin') || (
                $user->can('View:PayrollReports')
                && $user->can('ViewAmounts:PayrollRun')
            ));
    }

    public function mount(
        PayrollReconciliationReport $reconciliation,
        EmployeeAdvanceLedgerReport $advances,
        SalaryRegisterReport $salaryRegister,
        PayrollSummaryReport $summary,
        ProjectPayrollReport $projectPayroll,
    ): void {
        abort_unless(static::canAccess(), 403);
        $company = Filament::getTenant();
        $this->salaryRegisterRows = $salaryRegister->forCompany($company)->all();
        $this->summaryRows = $summary->forCompany($company)->all();
        $this->projectRows = $projectPayroll->forCompany($company)->all();
        $this->reconciliationRows = $reconciliation->forCompany($company)->all();
        $this->advanceRows = $advances->forCompany($company)->map(fn ($line): array => [
            'employee' => $line->employment->employee->full_name,
            'date' => $line->journalEntry->transaction_date->toDateString(),
            'voucher' => $line->journalEntry->voucher_number,
            'description' => $line->description,
            'advance' => $line->debit,
            'recovery' => $line->credit,
        ])->all();
    }

    public function export(string $report, HrReportCsvExporter $exporter, string $format = 'csv'): StreamedResponse
    {
        $user = Filament::auth()->user();
        abort_unless(static::canAccess() && ($user->hasRole('super_admin') || $user->can('Export:PayrollReports')), 403);

        [$rows, $columns] = match ($report) {
            'salary-register' => [collect($this->salaryRegisterRows)->map(fn (array $row): array => [
                'period' => $row['run']->period_end->format('M Y'),
                'employee_code' => $row['entry']->employee_code,
                'employee_name' => $row['entry']->employee_name,
                'gross' => $row['gross'],
                'attendance_deductions' => $row['attendance_deductions'],
                'financing_recovery' => $row['financing_recovery'],
                'other_deduction' => $row['other_deduction'],
                'net' => $row['net'],
            ]), [
                'period' => 'Period', 'employee_code' => 'Employee Code', 'employee_name' => 'Employee',
                'gross' => 'Gross', 'attendance_deductions' => 'Attendance Deductions',
                'financing_recovery' => 'Loan / Advance Recovery', 'other_deduction' => 'Other Deduction', 'net' => 'Net',
            ]],
            'payroll-summary' => [collect($this->summaryRows)->map(fn (array $row): array => [
                'reference' => $row['run']->reference_number,
                'period_start' => $row['run']->period_start->toDateString(),
                'period_end' => $row['run']->period_end->toDateString(),
                'status' => $row['run']->status->getLabel(),
                ...collect($row)->except('run')->all(),
            ]), [
                'reference' => 'Payroll', 'period_start' => 'Period Start', 'period_end' => 'Period End',
                'status' => 'Status', 'employees' => 'Employees', 'gross' => 'Gross',
                'bonus' => 'Bonus', 'incentive' => 'Incentive', 'attendance_deductions' => 'Attendance Deductions',
                'financing_recovery' => 'Loan / Advance Recovery', 'net' => 'Net',
            ]],
            'project-payroll' => [collect($this->projectRows)->map(fn (array $row): array => [
                'period' => $row['run']->period_end->format('M Y'),
                'project' => $row['project']->name,
                'site' => $row['site']?->name,
                'cost_center' => $row['cost_center']?->name,
                'employee_code' => $row['entry']->employee_code,
                'employee_name' => $row['entry']->employee_name,
                'amount' => $row['amount'],
            ]), [
                'period' => 'Period', 'project' => 'Project', 'site' => 'Site',
                'cost_center' => 'Cost Center', 'employee_code' => 'Employee Code',
                'employee_name' => 'Employee', 'amount' => 'Allocated Payroll',
            ]],
            default => abort(404),
        };

        return $exporter->download($user, Filament::getTenant(), $report, $rows, $columns, format: $format);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make($this->exportActions('csv'))->label('Export CSV')
                ->visible(fn (): bool => Filament::auth()->user()?->hasRole('super_admin')
                    || Filament::auth()->user()?->can('Export:PayrollReports')),
            ActionGroup::make($this->exportActions('xlsx'))->label('Export Excel')
                ->visible(fn (): bool => Filament::auth()->user()?->hasRole('super_admin')
                    || Filament::auth()->user()?->can('Export:PayrollReports')),
        ];
    }

    /** @return array<int, Action> */
    private function exportActions(string $format): array
    {
        $suffix = str($format)->headline()->toString();

        return [
            Action::make("exportSalaryRegister{$suffix}")->label('Salary Register')->action(fn () => $this->export('salary-register', app(HrReportCsvExporter::class), $format)),
            Action::make("exportPayrollSummary{$suffix}")->label('Payroll Summary')->action(fn () => $this->export('payroll-summary', app(HrReportCsvExporter::class), $format)),
            Action::make("exportProjectPayroll{$suffix}")->label('Project-wise Payroll')->action(fn () => $this->export('project-payroll', app(HrReportCsvExporter::class), $format)),
        ];
    }
}
