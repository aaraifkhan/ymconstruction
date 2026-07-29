<?php

namespace App\Filament\Pages;

use App\Reports\GroupHrReport;
use App\Reports\HrReportCsvExporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroupHrReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Group HR';

    protected string $view = 'filament.pages.group-hr-reports';

    /** @var array<int, string> */
    public array $companies = [];

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public string $period = '';

    public int $uniquePeople = 0;

    public int $employmentCount = 0;

    public bool $payrollVisible = false;

    public bool $financingVisible = false;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:GroupHrReports'));
    }

    public function mount(GroupHrReport $report): void
    {
        abort_unless(static::canAccess(), 403);
        $result = $report->forGroup(
            Filament::auth()->user(),
            Filament::getTenant(),
            today()->startOfYear(),
            today(),
        );
        $this->companies = $result['companies'];
        $this->rows = $result['rows']->all();
        $this->period = $result['period'];
        $this->uniquePeople = $result['unique_people'];
        $this->employmentCount = $result['employment_count'];
        $this->payrollVisible = $result['payroll_visible'];
        $this->financingVisible = $result['financing_visible'];
    }

    public function export(HrReportCsvExporter $exporter, string $format = 'csv'): StreamedResponse
    {
        $user = Filament::auth()->user();
        abort_unless(static::canAccess() && ($user->hasRole('super_admin') || $user->can('Export:GroupHrReports')), 403);

        return $exporter->download($user, Filament::getTenant(), 'group-hr', collect($this->rows), [
            'company' => 'Company', 'unique_people' => 'Unique People', 'employment_count' => 'Employments',
            'active' => 'Active', 'on_leave' => 'On Leave', 'joiners' => 'Joiners', 'exits' => 'Exits',
            'present_days' => 'Present Days', 'absent_days' => 'Absent Days', 'half_days' => 'Half Days',
            'late_minutes' => 'Late Minutes', 'leave_units' => 'Approved Leave Units',
            'payroll_cost' => 'Payroll Cost', 'loan_outstanding' => 'Loan Outstanding',
            'advance_outstanding' => 'Advance Outstanding',
        ], true, $format);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')->label('Export CSV')
                ->visible(fn (): bool => Filament::auth()->user()?->hasRole('super_admin')
                    || Filament::auth()->user()?->can('Export:GroupHrReports'))
                ->action(fn () => $this->export(app(HrReportCsvExporter::class))),
            Action::make('exportXlsx')->label('Export Excel')
                ->visible(fn (): bool => Filament::auth()->user()?->hasRole('super_admin')
                    || Filament::auth()->user()?->can('Export:GroupHrReports'))
                ->action(fn () => $this->export(app(HrReportCsvExporter::class), 'xlsx')),
        ];
    }
}
