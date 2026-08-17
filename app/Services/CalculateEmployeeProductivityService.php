<?php

namespace App\Services;

use App\Enums\DailyReportSubmissionStatus;
use App\Enums\TaskStatus;
use App\Models\DailyWorkReport;
use App\Models\Employment;
use App\Models\Task;
use Carbon\CarbonInterface;

class CalculateEmployeeProductivityService
{
    /**
     * @return array<string, mixed>
     */
    public function calculate(Employment $employment, ?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfDay();

        $tasksQuery = Task::query()
            ->where('assigned_to_employment_id', $employment->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalAssigned = (clone $tasksQuery)->count();
        $totalCompleted = (clone $tasksQuery)->where('status', TaskStatus::Completed->value)->count();
        $totalOverdue = (clone $tasksQuery)
            ->where('status', '!=', TaskStatus::Completed->value)
            ->whereNotNull('deadline_date')
            ->where('deadline_date', '<', now()->toDateString())
            ->count();

        $completionRate = $totalAssigned > 0 ? round(($totalCompleted / $totalAssigned) * 100, 1) : 100.0;

        // Turnaround time for completed tasks in hours
        $completedTasks = (clone $tasksQuery)
            ->where('status', TaskStatus::Completed->value)
            ->whereNotNull('completed_at')
            ->get();

        $totalTurnaroundHours = 0;
        $totalRevisions = 0;

        foreach ($completedTasks as $t) {
            $totalTurnaroundHours += $t->created_at->diffInHours($t->completed_at);
            $totalRevisions += $t->revision_count;
        }

        $avgTurnaroundHours = $totalCompleted > 0 ? round($totalTurnaroundHours / $totalCompleted, 1) : 0.0;
        $avgRevisions = $totalCompleted > 0 ? round($totalRevisions / $totalCompleted, 2) : 0.0;

        // Daily report metrics
        $reportsQuery = DailyWorkReport::query()
            ->where('employment_id', $employment->id)
            ->whereDate('report_date', '>=', $startDate->toDateString())
            ->whereDate('report_date', '<=', $endDate->toDateString());

        $reportsSubmittedOnTime = (clone $reportsQuery)->where('submission_status', DailyReportSubmissionStatus::OnTime->value)->count();
        $reportsSubmittedLate = (clone $reportsQuery)->where('submission_status', DailyReportSubmissionStatus::Late->value)->count();
        $totalReportsSubmitted = $reportsSubmittedOnTime + $reportsSubmittedLate;

        $workingDays = max(1, $startDate->diffInWeekdays($endDate) ?: 1);
        $onTimeReportRate = round(($reportsSubmittedOnTime / $workingDays) * 100, 1);

        // Composite Productivity Score (0 - 100)
        // Weighted: 50% Task Completion, 30% On-time Daily Reporting, 20% Low Revisions penalty
        $revisionPenalty = min(20, $avgRevisions * 5);
        $productivityScore = round(
            ($completionRate * 0.50) + ($onTimeReportRate * 0.30) + (20 - $revisionPenalty),
            1
        );

        return [
            'employment_id' => $employment->id,
            'employee_name' => $employment->employee?->full_name ?? 'N/A',
            'employee_code' => $employment->employee_code,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'tasks_assigned' => $totalAssigned,
            'tasks_completed' => $totalCompleted,
            'tasks_overdue' => $totalOverdue,
            'task_completion_rate' => $completionRate,
            'avg_turnaround_hours' => $avgTurnaroundHours,
            'total_revisions' => $totalRevisions,
            'avg_revisions_per_task' => $avgRevisions,
            'reports_submitted_on_time' => $reportsSubmittedOnTime,
            'reports_submitted_late' => $reportsSubmittedLate,
            'total_reports_submitted' => $totalReportsSubmitted,
            'on_time_report_rate' => min(100.0, $onTimeReportRate),
            'productivity_score' => min(100.0, max(0.0, $productivityScore)),
        ];
    }
}
