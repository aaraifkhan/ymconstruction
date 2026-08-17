<?php

namespace App\Actions\DailyReports;

use App\Enums\DailyReportSubmissionStatus;
use App\Models\DailyWorkReport;
use App\Models\Employment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubmitDailyReportAction
{
    public function handle(
        Employment $employment,
        array $reportData,
        array $taskItems = [],
        ?array $salesDetailData = null
    ): DailyWorkReport {
        return DB::transaction(function () use ($employment, $reportData, $taskItems, $salesDetailData): DailyWorkReport {
            $reportDate = Carbon::parse($reportData['report_date'] ?? now()->toDateString());
            $now = now();

            // 6:00 PM (18:00) Cutoff evaluation
            $deadlineTime = $reportDate->copy()->setTime(18, 0, 0);
            $submissionStatus = $now->greaterThan($deadlineTime)
                ? DailyReportSubmissionStatus::Late
                : DailyReportSubmissionStatus::OnTime;

            $completedCount = 0;
            $inProgressCount = 0;
            $pendingCount = 0;
            $totalProgress = 0;
            $deliverablesCount = 0;

            foreach ($taskItems as $item) {
                $status = $item['status_today'] ?? 'in_progress';
                if ($status === 'completed') {
                    $completedCount++;
                } elseif ($status === 'in_progress') {
                    $inProgressCount++;
                } else {
                    $pendingCount++;
                }

                $totalProgress += (int) ($item['progress_percentage'] ?? 0);
                if (! empty($item['deliverable_summary']) || ! empty($item['work_links'])) {
                    $deliverablesCount++;
                }
            }

            $totalItems = count($taskItems);
            $avgProgress = $totalItems > 0 ? (int) round($totalProgress / $totalItems) : (int) ($reportData['overall_progress_percentage'] ?? 0);

            /** @var DailyWorkReport $report */
            $report = DailyWorkReport::updateOrCreate(
                [
                    'employment_id' => $employment->id,
                    'report_date' => $reportDate->toDateString(),
                ],
                [
                    'company_id' => $employment->company_id,
                    'department_id' => $employment->department_id,
                    'department_team_id' => $reportData['department_team_id'] ?? $employment->teamMemberships()->where('is_active', true)->value('department_team_id'),
                    'submitted_at' => $now,
                    'submission_status' => $submissionStatus,
                    'tasks_completed_count' => $completedCount,
                    'tasks_in_progress_count' => $inProgressCount,
                    'tasks_pending_count' => $pendingCount,
                    'overall_progress_percentage' => min(100, max(0, $avgProgress)),
                    'deliverables_count' => $deliverablesCount,
                    'blockers_summary' => $reportData['blockers_summary'] ?? null,
                    'additional_comments' => $reportData['additional_comments'] ?? null,
                ]
            );

            // Sync items
            $report->taskItems()->delete();
            foreach ($taskItems as $item) {
                $report->taskItems()->create([
                    'task_id' => $item['task_id'] ?? null,
                    'task_title' => $item['task_title'] ?? 'General Task',
                    'status_today' => $item['status_today'] ?? 'in_progress',
                    'hours_spent' => $item['hours_spent'] ?? 0,
                    'progress_percentage' => $item['progress_percentage'] ?? 0,
                    'deliverable_summary' => $item['deliverable_summary'] ?? null,
                    'work_links' => $item['work_links'] ?? null,
                    'blockers' => $item['blockers'] ?? null,
                ]);
            }

            // Sync sales details if provided
            if ($salesDetailData !== null) {
                $report->salesDetail()->updateOrCreate(
                    ['daily_work_report_id' => $report->id],
                    $salesDetailData
                );
            }

            return $report;
        });
    }
}
