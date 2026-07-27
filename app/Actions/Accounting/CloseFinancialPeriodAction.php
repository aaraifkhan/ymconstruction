<?php

namespace App\Actions\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Models\FinancialPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseFinancialPeriodAction
{
    public function handle(FinancialPeriod $period, User $actor): FinancialPeriod
    {
        return DB::transaction(function () use ($period, $actor): FinancialPeriod {
            $period = FinancialPeriod::query()->lockForUpdate()->findOrFail($period->getKey());
            if ($period->status !== FinancialPeriodStatus::Open) {
                throw ValidationException::withMessages(['status' => 'Only an open period can be closed.']);
            }
            $period->update(['status' => FinancialPeriodStatus::Closed, 'closed_by_id' => $actor->getKey(), 'closed_at' => now()]);
            activity('financial_periods')->causedBy($actor)->performedOn($period)->log('Financial period closed');

            return $period;
        });
    }
}
