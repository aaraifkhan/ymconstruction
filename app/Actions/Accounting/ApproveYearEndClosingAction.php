<?php

namespace App\Actions\Accounting;

use App\Enums\YearEndClosingStatus;
use App\Models\User;
use App\Models\YearEndClosing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveYearEndClosingAction
{
    public function handle(YearEndClosing $closing, User $actor): YearEndClosing
    {
        Gate::forUser($actor)->authorize('approve', $closing);

        return DB::transaction(function () use ($closing, $actor): YearEndClosing {
            $closing = YearEndClosing::query()->whereKey($closing)->lockForUpdate()->firstOrFail();
            if ($closing->status !== YearEndClosingStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only a draft year-end closing may be approved.']);
            }
            if ((int) $closing->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['actor' => 'The preparer cannot approve the year-end closing.']);
            }
            $closing->update(['status' => YearEndClosingStatus::Approved, 'approved_by_id' => $actor->getKey(), 'approved_at' => now()]);
            activity('year_end_closings')->causedBy($actor)->performedOn($closing)->event('approved')->log('approved year-end closing');

            return $closing->refresh();
        });
    }
}
