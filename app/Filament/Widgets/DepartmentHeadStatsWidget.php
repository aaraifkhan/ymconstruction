<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Models\DailyWorkReport;
use App\Models\Employment;
use App\Models\Task;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DepartmentHeadStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $company = Filament::getTenant();
        if (! $company) {
            return [];
        }

        $totalEmployees = Employment::query()
            ->where('company_id', $company->id)
            ->where('employment_status', '!=', 'ended')
            ->count();

        $today = now()->toDateString();

        $submittedReports = DailyWorkReport::query()
            ->where('company_id', $company->id)
            ->where('report_date', $today)
            ->count();

        $pendingReports = max(0, $totalEmployees - $submittedReports);

        $activeTasks = Task::query()
            ->where('company_id', $company->id)
            ->whereIn('status', [TaskStatus::InProgress, TaskStatus::Submitted, TaskStatus::RevisionRequired])
            ->count();

        $completedToday = Task::query()
            ->where('company_id', $company->id)
            ->where('status', TaskStatus::Completed)
            ->whereDate('completed_at', $today)
            ->count();

        $overdueTasks = Task::query()
            ->where('company_id', $company->id)
            ->where('status', '!=', TaskStatus::Completed)
            ->whereNotNull('deadline_date')
            ->where('deadline_date', '<', $today)
            ->count();

        $awaitingApproval = Task::query()
            ->where('company_id', $company->id)
            ->whereIn('status', [TaskStatus::Submitted, TaskStatus::Approved])
            ->whereNull('head_approved_at')
            ->count();

        return [
            Stat::make('Total Staff', $totalEmployees)
                ->description("{$submittedReports} Submitted / {$pendingReports} Pending")
                ->descriptionIcon('heroicon-m-users')
                ->color($pendingReports > 0 ? 'warning' : 'success'),

            Stat::make('Tasks In Progress', $activeTasks)
                ->description("{$completedToday} completed today")
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),

            Stat::make('Overdue Tasks', $overdueTasks)
                ->description('Passed deadline')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueTasks > 0 ? 'danger' : 'success'),

            Stat::make('Awaiting Final Approval', $awaitingApproval)
                ->description('Pending Head Review')
                ->descriptionIcon('heroicon-m-clock')
                ->color($awaitingApproval > 0 ? 'warning' : 'success'),
        ];
    }
}
