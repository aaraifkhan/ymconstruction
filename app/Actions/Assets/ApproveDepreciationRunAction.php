<?php

namespace App\Actions\Assets;

use App\Enums\AssetAccountingStatus;
use App\Models\DepreciationRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveDepreciationRunAction
{
    public function handle(DepreciationRun $run, User $actor): DepreciationRun
    {
        Gate::forUser($actor)->authorize('approve', $run);

        return DB::transaction(function () use ($run, $actor): DepreciationRun {
            $run = DepreciationRun::query()->whereKey($run)->lockForUpdate()->firstOrFail();
            if ($run->status !== AssetAccountingStatus::Submitted || (int) $run->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['status' => 'An independent approver is required.']);
            }
            $run->update(['status' => AssetAccountingStatus::Approved, 'approved_by_id' => $actor->getKey(), 'approved_at' => now()]);

            return $run->refresh();
        });
    }
}
