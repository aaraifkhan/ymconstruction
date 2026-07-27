<?php

namespace App\Actions\Assets;

use App\Enums\AssetAccountingStatus;
use App\Models\DepreciationRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitDepreciationRunAction
{
    public function handle(DepreciationRun $run, User $actor): DepreciationRun
    {
        Gate::forUser($actor)->authorize('submit', $run);

        return DB::transaction(function () use ($run, $actor): DepreciationRun {
            $run = DepreciationRun::query()->whereKey($run)->lockForUpdate()->firstOrFail();
            if ($run->status !== AssetAccountingStatus::Draft || $run->lines()->doesntExist()) {
                throw ValidationException::withMessages(['status' => 'Generate a draft run before submission.']);
            }
            $run->update(['status' => AssetAccountingStatus::Submitted, 'submitted_by_id' => $actor->getKey(), 'submitted_at' => now()]);

            return $run->refresh();
        });
    }
}
