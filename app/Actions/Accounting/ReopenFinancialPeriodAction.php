<?php

namespace App\Actions\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Models\FinancialPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReopenFinancialPeriodAction
{
    public function handle(FinancialPeriod $period, User $actor, string $reason): FinancialPeriod
    {
        if (blank($reason)) {
            throw ValidationException::withMessages(['reopen_reason' => 'A reopen reason is required.']);
        }

        return DB::transaction(function () use ($period, $actor, $reason): FinancialPeriod {
            $period = FinancialPeriod::query()->lockForUpdate()->findOrFail($period->getKey());
            if ($period->status === FinancialPeriodStatus::Open) {
                throw ValidationException::withMessages(['status' => 'The period is already open.']);
            }
            $period->update(['status' => FinancialPeriodStatus::Open, 'reopened_by_id' => $actor->getKey(), 'reopened_at' => now(), 'reopen_reason' => $reason]);
            activity('financial_periods')->causedBy($actor)->performedOn($period)->withProperties(['reason' => $reason])->log('Financial period reopened');

            return $period;
        });
    }
}
