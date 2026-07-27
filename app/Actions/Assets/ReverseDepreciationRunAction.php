<?php

namespace App\Actions\Assets;

use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Enums\AssetAccountingStatus;
use App\Models\DepreciationRun;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseDepreciationRunAction
{
    public function __construct(private ReverseJournalEntryAction $reverseJournalEntry) {}

    public function handle(DepreciationRun $run, User $actor, CarbonInterface $date, string $reason): DepreciationRun
    {
        Gate::forUser($actor)->authorize('reverse', $run);

        return DB::transaction(function () use ($run, $actor, $date, $reason): DepreciationRun {
            $run = DepreciationRun::query()->with(['lines.fixedAsset', 'journalEntry'])->whereKey($run)->lockForUpdate()->firstOrFail();
            if ($run->status === AssetAccountingStatus::Reversed) {
                return $run;
            }
            if ($run->status !== AssetAccountingStatus::Posted || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'A posted run and reversal reason are required.']);
            }
            foreach ($run->lines as $line) {
                if (bccomp((string) $line->fixedAsset->accumulated_depreciation, (string) $line->closing_accumulated_depreciation, 4) !== 0) {
                    throw ValidationException::withMessages(['assets' => 'Later asset activity must be reversed first.']);
                }
            }
            $reversal = $this->reverseJournalEntry->handle($run->journalEntry, $actor, $date, $reason);
            foreach ($run->lines as $line) {
                $line->fixedAsset->update(['accumulated_depreciation' => $line->opening_accumulated_depreciation]);
            }
            $run->update(['status' => AssetAccountingStatus::Reversed, 'reversal_journal_entry_id' => $reversal->getKey(), 'reversed_by_id' => $actor->getKey(), 'reversed_at' => now()]);

            return $run->refresh();
        }, attempts: 3);
    }
}
