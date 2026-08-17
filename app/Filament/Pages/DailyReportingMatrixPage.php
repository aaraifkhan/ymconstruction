<?php

namespace App\Filament\Pages;

use App\Enums\DailyReportSubmissionStatus;
use App\Models\DailyWorkReport;
use App\Models\DepartmentTeam;
use App\Models\Employment;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DailyReportingMatrixPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static \UnitEnum|string|null $navigationGroup = 'Department Operations';

    protected static ?string $navigationLabel = 'Daily Attendance & Work Matrix';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.daily-reporting-matrix-page';

    public string $selectedDate;

    public ?int $selectedTeamId = null;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function getTeamsProperty(): array
    {
        $company = Filament::getTenant();
        if (! $company) {
            return [];
        }

        return DepartmentTeam::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getMatrixDataProperty(): array
    {
        $company = Filament::getTenant();
        if (! $company) {
            return [
                'summary' => ['total' => 0, 'on_time' => 0, 'late' => 0, 'missing' => 0],
                'records' => [],
            ];
        }

        $employmentsQuery = Employment::query()
            ->where('company_id', $company->id)
            ->where('employment_status', '!=', 'ended')
            ->with(['employee', 'department', 'designation', 'teamMemberships.team']);

        if ($this->selectedTeamId) {
            $employmentsQuery->whereHas('teamMemberships', fn ($q) => $q->where('department_team_id', $this->selectedTeamId)->where('is_active', true));
        }

        $employments = $employmentsQuery->get();

        $reports = DailyWorkReport::query()
            ->where('company_id', $company->id)
            ->where('report_date', $this->selectedDate)
            ->with(['taskItems', 'salesDetail'])
            ->get()
            ->keyBy('employment_id');

        $onTimeCount = 0;
        $lateCount = 0;
        $missingCount = 0;
        $records = [];

        foreach ($employments as $emp) {
            /** @var DailyWorkReport|null $report */
            $report = $reports->get($emp->id);
            $activeTeam = $emp->teamMemberships->where('is_active', true)->first()?->team;

            if ($report) {
                if ($report->submission_status === DailyReportSubmissionStatus::OnTime) {
                    $status = 'on_time';
                    $statusLabel = '🟢 On Time';
                    $statusBadge = 'success';
                    $onTimeCount++;
                } else {
                    $status = 'late';
                    $statusLabel = '🟠 Late';
                    $statusBadge = 'warning';
                    $lateCount++;
                }
            } else {
                $status = 'missing';
                $statusLabel = '🔴 Missing';
                $statusBadge = 'danger';
                $missingCount++;
            }

            $records[] = [
                'employment_id' => $emp->id,
                'employee_name' => $emp->employee?->full_name ?? 'N/A',
                'employee_code' => $emp->employee_code,
                'designation' => $emp->designation?->name ?? '—',
                'department' => $emp->department?->name ?? '—',
                'team' => $activeTeam?->name ?? 'General',
                'status' => $status,
                'status_label' => $statusLabel,
                'status_badge' => $statusBadge,
                'submitted_at' => $report?->submitted_at?->format('h:i A') ?? '—',
                'progress' => $report?->overall_progress_percentage ?? 0,
                'deliverables' => $report?->deliverables_count ?? 0,
                'tasks_completed' => $report?->tasks_completed_count ?? 0,
                'tasks_in_progress' => $report?->tasks_in_progress_count ?? 0,
                'blockers' => $report?->blockers_summary,
                'items' => $report?->taskItems->toArray() ?? [],
                'report_id' => $report?->id,
            ];
        }

        return [
            'summary' => [
                'total' => count($employments),
                'on_time' => $onTimeCount,
                'late' => $lateCount,
                'missing' => $missingCount,
            ],
            'records' => $records,
        ];
    }
}
