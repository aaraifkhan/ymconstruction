<?php

namespace App\Filament\Widgets;

use App\Enums\DailyReportSubmissionStatus;
use App\Models\DailyWorkReport;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class DailyReportingMatrixStatsWidget extends StatsOverviewWidget
{
    #[Reactive]
    public ?string $selectedDate = null;

    #[Reactive]
    public ?int $selectedTeamId = null;

    protected function getStats(): array
    {
        $company = Filament::getTenant();
        if (! $company) {
            return [];
        }

        $date = $this->selectedDate ?? now()->toDateString();

        $employmentsQuery = Employment::query()
            ->where('company_id', $company->id)
            ->where('employment_status', '!=', 'ended');

        if ($this->selectedTeamId) {
            $employmentsQuery->whereHas('teamMemberships', fn ($q) => $q->where('department_team_id', $this->selectedTeamId)->where('is_active', true));
        }

        $totalEmployees = $employmentsQuery->count();

        $reportsQuery = DailyWorkReport::query()
            ->where('company_id', $company->id)
            ->where('report_date', $date);

        if ($this->selectedTeamId) {
            $reportsQuery->where('department_team_id', $this->selectedTeamId);
        }

        $onTimeCount = (clone $reportsQuery)->where('submission_status', DailyReportSubmissionStatus::OnTime)->count();
        $lateCount = (clone $reportsQuery)->where('submission_status', DailyReportSubmissionStatus::Late)->count();
        $submittedTotal = $onTimeCount + $lateCount;
        $missingCount = max(0, $totalEmployees - $submittedTotal);

        $onTimePercentage = $totalEmployees > 0 ? round(($onTimeCount / $totalEmployees) * 100) : 0;

        return [
            Stat::make('Total Active Staff', $totalEmployees)
                ->description("Reporting date: {$date}")
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('🟢 On-Time (<= 6:00 PM)', $onTimeCount)
                ->description("{$onTimePercentage}% on-time submission rate")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('🟠 Late Submission (> 6:00 PM)', $lateCount)
                ->description('Submitted after 6 PM cutoff')
                ->descriptionIcon('heroicon-m-clock')
                ->color($lateCount > 0 ? 'warning' : 'gray'),

            Stat::make('🔴 Missing Submissions', $missingCount)
                ->description($missingCount > 0 ? 'Action required: reminder needed' : 'All staff submitted!')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($missingCount > 0 ? 'danger' : 'success'),
        ];
    }
}
