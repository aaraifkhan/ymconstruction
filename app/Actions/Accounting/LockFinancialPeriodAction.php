<?php

namespace App\Actions\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Models\FinancialPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LockFinancialPeriodAction
{
    public function handle(FinancialPeriod $period, User $actor): FinancialPeriod
    {
        return DB::transaction(function () use ($period, $actor): FinancialPeriod {
            $period = FinancialPeriod::query()->lockForUpdate()->findOrFail($period->getKey());
            if ($period->status !== FinancialPeriodStatus::Closed) {
                throw ValidationException::withMessages(['status' => 'Only a closed period can be locked.']);
            }
            $period->update(['status' => FinancialPeriodStatus::Locked, 'locked_by_id' => $actor->getKey(), 'locked_at' => now()]);
            activity('financial_periods')->causedBy($actor)->performedOn($period)->log('Financial period locked');

            return $period;
        });
    }
}
