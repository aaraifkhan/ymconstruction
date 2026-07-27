<?php

namespace App\Actions\Assets;

use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Enums\AssetAccountingStatus;
use App\Enums\AssetStatus;
use App\Models\AssetDisposal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseAssetDisposalAction
{
    public function __construct(private ReverseJournalEntryAction $reverseJournalEntry) {}

    public function handle(AssetDisposal $disposal, User $actor, CarbonInterface $date, string $reason): AssetDisposal
    {
        Gate::forUser($actor)->authorize('reverse', $disposal);

        return DB::transaction(function () use ($disposal, $actor, $date, $reason): AssetDisposal {
            $disposal = AssetDisposal::query()->with(['fixedAsset', 'journalEntry'])->whereKey($disposal)->lockForUpdate()->firstOrFail();
            if ($disposal->status === AssetAccountingStatus::Reversed) {
                return $disposal;
            }
            if ($disposal->status !== AssetAccountingStatus::Posted || blank($reason)) {
                throw ValidationException::withMessages(['status' => 'A posted disposal and reason are required.']);
            }
            $reversal = $this->reverseJournalEntry->handle($disposal->journalEntry, $actor, $date, $reason);
            $disposal->fixedAsset->update(['status' => AssetStatus::Active]);
            $disposal->update(['status' => AssetAccountingStatus::Reversed, 'reversal_journal_entry_id' => $reversal->getKey(), 'reversed_by_id' => $actor->getKey(), 'reversed_at' => now()]);

            return $disposal->refresh();
        });
    }
}
