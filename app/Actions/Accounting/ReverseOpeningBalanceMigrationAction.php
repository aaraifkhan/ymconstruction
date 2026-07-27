<?php

namespace App\Actions\Accounting;

use App\Enums\OpeningBalanceMigrationStatus;
use App\Models\JournalEntry;
use App\Models\OpeningBalanceMigration;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseOpeningBalanceMigrationAction
{
    public function __construct(private ReverseJournalEntryAction $reverseJournal) {}

    public function handle(OpeningBalanceMigration $migration, User $actor, CarbonInterface $date, string $reason): OpeningBalanceMigration
    {
        Gate::forUser($actor)->authorize('reverse', $migration);
        if (blank($reason)) {
            throw ValidationException::withMessages(['reversal_reason' => 'A rollback reason is required.']);
        }

        return DB::transaction(function () use ($migration, $actor, $date, $reason): OpeningBalanceMigration {
            $migration = OpeningBalanceMigration::query()->with('openingBalanceBatch')->whereKey($migration)->lockForUpdate()->firstOrFail();
            if ($migration->status === OpeningBalanceMigrationStatus::Reversed) {
                return $migration;
            }
            if ($migration->status !== OpeningBalanceMigrationStatus::Imported) {
                throw ValidationException::withMessages(['status' => 'Only an imported migration may be rolled back.']);
            }
            $reversal = $this->reverseJournal->handle(
                JournalEntry::findOrFail($migration->openingBalanceBatch->journal_entry_id),
                $actor,
                $date,
                $reason,
            );
            $migration->update([
                'status' => OpeningBalanceMigrationStatus::Reversed,
                'reversed_by_id' => $actor->getKey(),
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'reversal_entry_id' => $reversal->getKey(),
            ]);
            activity('opening_balance_migrations')->causedBy($actor)->performedOn($migration)->event('reversed')
                ->withProperties(['reason' => $reason, 'reversal_entry_id' => $reversal->getKey()])
                ->log('rolled back opening-balance migration');

            return $migration->refresh();
        }, attempts: 3);
    }
}
