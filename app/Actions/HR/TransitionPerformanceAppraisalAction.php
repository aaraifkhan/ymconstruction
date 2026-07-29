<?php

namespace App\Actions\HR;

use App\Enums\AppraisalCycleStatus;
use App\Enums\PerformanceAppraisalStatus;
use App\Models\PerformanceAppraisal;
use App\Models\PerformanceAppraisalItem;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransitionPerformanceAppraisalAction
{
    public function submit(PerformanceAppraisal $appraisal, User $actor): PerformanceAppraisal
    {
        Gate::forUser($actor)->authorize('submit', $appraisal);

        return DB::transaction(function () use ($appraisal, $actor): PerformanceAppraisal {
            $appraisal = PerformanceAppraisal::query()->with(['cycle', 'items.kpi'])
                ->whereKey($appraisal)->lockForUpdate()->firstOrFail();
            if (! in_array($appraisal->status, [
                PerformanceAppraisalStatus::Draft, PerformanceAppraisalStatus::Rejected,
            ], true) || $appraisal->cycle->status !== AppraisalCycleStatus::Active
                || $appraisal->items->isEmpty()
                || bccomp((string) $appraisal->items->sum('weight'), '100', 4) !== 0) {
                throw ValidationException::withMessages([
                    'items' => 'Submission requires an active cycle and KPI weights totaling exactly 100.',
                ]);
            }
            $checksum = hash('sha256', json_encode($appraisal->items->sortBy('performance_kpi_id')
                ->map(fn (PerformanceAppraisalItem $item): array => [
                    'kpi_id' => $item->performance_kpi_id,
                    'kpi_code' => $item->kpi->code,
                    'goal' => $item->goal,
                    'weight' => (string) $item->weight,
                ])->values()->all(), JSON_THROW_ON_ERROR));
            $appraisal->update([
                'status' => PerformanceAppraisalStatus::Submitted,
                'source_checksum' => $checksum,
                'submitted_by_id' => $actor->getKey(),
                'submitted_at' => now(),
                'rejected_by_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
            $this->audit($appraisal, $actor, 'submitted');

            return $appraisal;
        }, 3);
    }

    /** @param array<int, array{score: string|int|float, comments?: string|null}> $scores */
    public function review(PerformanceAppraisal $appraisal, array $scores, string $outcome, User $actor): PerformanceAppraisal
    {
        Gate::forUser($actor)->authorize('review', $appraisal);

        return DB::transaction(function () use ($appraisal, $scores, $outcome, $actor): PerformanceAppraisal {
            $appraisal = PerformanceAppraisal::query()->with(['cycle', 'items'])
                ->whereKey($appraisal)->lockForUpdate()->firstOrFail();
            if ($appraisal->status !== PerformanceAppraisalStatus::Submitted
                || (int) $appraisal->submitted_by_id === (int) $actor->getKey()
                || (int) $appraisal->created_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'An independent reviewer must review a submitted appraisal.']);
            }
            $weighted = '0.0000';
            foreach ($appraisal->items as $item) {
                $score = (string) ($scores[$item->getKey()]['score'] ?? '');
                if (! is_numeric($score)
                    || bccomp($score, (string) $appraisal->cycle->score_min, 4) === -1
                    || bccomp($score, (string) $appraisal->cycle->score_max, 4) === 1) {
                    throw ValidationException::withMessages(['scores' => 'Every KPI requires a score within the cycle scale.']);
                }
                PerformanceAppraisalItem::query()->whereKey($item)->update([
                    'score' => Crypt::encryptString(number_format((float) $score, 4, '.', '')),
                    'reviewer_comments' => filled($scores[$item->getKey()]['comments'] ?? null)
                        ? Crypt::encryptString($scores[$item->getKey()]['comments'])
                        : null,
                    'updated_at' => now(),
                ]);
                $weighted = bcadd($weighted, bcmul($score, bcdiv((string) $item->weight, '100', 8), 8), 4);
            }
            $appraisal->update([
                'status' => PerformanceAppraisalStatus::Reviewed,
                'overall_score' => $weighted,
                'outcome' => $outcome,
                'reviewed_by_id' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);
            $this->audit($appraisal, $actor, 'reviewed');

            return $appraisal;
        }, 3);
    }

    public function approve(PerformanceAppraisal $appraisal, User $actor): PerformanceAppraisal
    {
        Gate::forUser($actor)->authorize('approve', $appraisal);

        return DB::transaction(function () use ($appraisal, $actor): PerformanceAppraisal {
            $appraisal = PerformanceAppraisal::query()->whereKey($appraisal)->lockForUpdate()->firstOrFail();
            if ($appraisal->status !== PerformanceAppraisalStatus::Reviewed
                || in_array((int) $actor->getKey(), [
                    (int) $appraisal->created_by_id,
                    (int) $appraisal->submitted_by_id,
                    (int) $appraisal->reviewed_by_id,
                ], true)) {
                throw ValidationException::withMessages(['status' => 'Appraisal approval requires an independent approver.']);
            }
            $appraisal->update([
                'status' => PerformanceAppraisalStatus::Approved,
                'approved_by_id' => $actor->getKey(),
                'approved_at' => now(),
            ]);
            $this->audit($appraisal, $actor, 'approved');

            return $appraisal;
        }, 3);
    }

    public function acknowledge(PerformanceAppraisal $appraisal, string $comments, User $actor): PerformanceAppraisal
    {
        Gate::forUser($actor)->authorize('acknowledge', $appraisal);

        return DB::transaction(function () use ($appraisal, $comments, $actor): PerformanceAppraisal {
            $appraisal = PerformanceAppraisal::query()->whereKey($appraisal)->lockForUpdate()->firstOrFail();
            if ($appraisal->status !== PerformanceAppraisalStatus::Approved) {
                throw ValidationException::withMessages(['status' => 'Only an approved appraisal may be acknowledged.']);
            }
            $appraisal->update([
                'status' => PerformanceAppraisalStatus::Acknowledged,
                'employee_comments' => $comments,
                'acknowledged_by_id' => $actor->getKey(),
                'acknowledged_at' => now(),
            ]);
            $this->audit($appraisal, $actor, 'acknowledged');

            return $appraisal;
        }, 3);
    }

    public function reject(PerformanceAppraisal $appraisal, string $reason, User $actor): PerformanceAppraisal
    {
        Gate::forUser($actor)->authorize('reject', $appraisal);

        return DB::transaction(function () use ($appraisal, $reason, $actor): PerformanceAppraisal {
            $appraisal = PerformanceAppraisal::query()->whereKey($appraisal)->lockForUpdate()->firstOrFail();
            if (! in_array($appraisal->status, [
                PerformanceAppraisalStatus::Submitted, PerformanceAppraisalStatus::Reviewed,
            ], true) || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'A submitted/reviewed appraisal and rejection reason are required.']);
            }
            $appraisal->update([
                'status' => PerformanceAppraisalStatus::Rejected,
                'rejected_by_id' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            $this->audit($appraisal, $actor, 'rejected');

            return $appraisal;
        }, 3);
    }

    private function audit(PerformanceAppraisal $appraisal, User $actor, string $event): void
    {
        activity('performance_appraisals')->causedBy($actor)->performedOn($appraisal)
            ->event($event)->withProperties([
                'company_id' => $appraisal->company_id,
                'source_checksum' => $appraisal->source_checksum,
            ])->log("{$event} performance appraisal");
    }
}
