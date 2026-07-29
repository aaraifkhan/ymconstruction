<?php

namespace App\Actions\HR;

use App\Enums\AppraisalCycleStatus;
use App\Models\AppraisalCycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransitionAppraisalCycleAction
{
    public function activate(AppraisalCycle $cycle, User $actor): AppraisalCycle
    {
        Gate::forUser($actor)->authorize('activate', $cycle);

        return DB::transaction(function () use ($cycle, $actor): AppraisalCycle {
            $cycle = AppraisalCycle::query()->whereKey($cycle)->lockForUpdate()->firstOrFail();
            if ($cycle->status !== AppraisalCycleStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only a draft cycle may be activated.']);
            }
            $cycle->update([
                'status' => AppraisalCycleStatus::Active,
                'activated_by_id' => $actor->getKey(),
                'activated_at' => now(),
            ]);
            activity('appraisal_cycles')->causedBy($actor)->performedOn($cycle)
                ->event('activated')->withProperties(['company_id' => $cycle->company_id])
                ->log('activated appraisal cycle');

            return $cycle;
        }, 3);
    }

    public function close(AppraisalCycle $cycle, User $actor): AppraisalCycle
    {
        Gate::forUser($actor)->authorize('close', $cycle);

        return DB::transaction(function () use ($cycle, $actor): AppraisalCycle {
            $cycle = AppraisalCycle::query()->whereKey($cycle)->lockForUpdate()->firstOrFail();
            if ($cycle->status !== AppraisalCycleStatus::Active
                || $cycle->appraisals()->whereNotIn('status', [
                    'approved', 'acknowledged', 'rejected',
                ])->exists()) {
                throw ValidationException::withMessages(['status' => 'Resolve every appraisal before closing an active cycle.']);
            }
            $cycle->update([
                'status' => AppraisalCycleStatus::Closed,
                'closed_by_id' => $actor->getKey(),
                'closed_at' => now(),
            ]);

            return $cycle;
        }, 3);
    }
}
