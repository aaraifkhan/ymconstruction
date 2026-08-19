<?php

namespace App\Filament\Widgets;

use App\Models\Employment;
use App\Services\CalculateEmployeeProductivityService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class EmployeePerformanceStatsWidget extends StatsOverviewWidget
{
    #[Reactive]
    public ?int $selectedEmploymentId = null;

    #[Reactive]
    public ?string $startDate = null;

    #[Reactive]
    public ?string $endDate = null;

    protected function getStats(): array
    {
        if (! $this->selectedEmploymentId) {
            return [];
        }

        $employment = Employment::with(['employee', 'department', 'designation'])->find($this->selectedEmploymentId);
        if (! $employment) {
            return [];
        }

        $start = $this->startDate ? Carbon::parse($this->startDate) : now()->startOfMonth();
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfDay();

        $service = app(CalculateEmployeeProductivityService::class);
        $data = $service->calculate($employment, $start, $end);

        $score = $data['productivity_score'];
        $scoreLabel = match (true) {
            $score >= 85 => '🌟 Top Performer',
            $score >= 70 => '⚡ Solid Performer',
            default => '⚠️ Needs Improvement',
        };
        $scoreColor = match (true) {
            $score >= 85 => 'success',
            $score >= 70 => 'info',
            default => 'danger',
        };

        return [
            Stat::make('🏆 Productivity Score', "{$score} / 100")
                ->description($scoreLabel)
                ->descriptionIcon('heroicon-m-trophy')
                ->color($scoreColor),

            Stat::make('📋 Task Completion Rate', "{$data['task_completion_rate']}%")
                ->description("{$data['tasks_completed']} completed / {$data['tasks_assigned']} assigned")
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($data['task_completion_rate'] >= 80 ? 'success' : 'warning'),

            Stat::make('⏱️ Avg Turnaround Time', "{$data['avg_turnaround_hours']} hrs")
                ->description('From creation to completion')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('⚠️ Overdue Tasks', $data['tasks_overdue'])
                ->description($data['tasks_overdue'] === 0 ? '100% on-time completion' : 'Tasks missed deadline')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($data['tasks_overdue'] > 0 ? 'danger' : 'success'),

            Stat::make('🔄 Revision Rate', "{$data['avg_revisions_per_task']} / task")
                ->description("{$data['total_revisions']} total revisions requested")
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($data['avg_revisions_per_task'] <= 0.5 ? 'success' : 'warning'),

            Stat::make('📅 On-Time Daily Reports', "{$data['on_time_report_rate']}%")
                ->description("{$data['reports_submitted_on_time']} on-time / {$data['reports_submitted_late']} late")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($data['on_time_report_rate'] >= 85 ? 'success' : 'warning'),
        ];
    }
}
