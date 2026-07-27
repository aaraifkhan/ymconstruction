<?php

namespace App\Actions\Accounting;

use App\Enums\OpeningBalanceMigrationStatus;
use App\Enums\OpeningBalanceStatus;
use App\Models\OpeningBalanceBatch;
use App\Models\OpeningBalanceLine;
use App\Models\OpeningBalanceMigration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ImportOpeningBalanceMigrationAction
{
    public function __construct(
        private ValidateOpeningBalanceBatchAction $validateBatch,
        private PostOpeningBalanceBatchAction $postBatch,
    ) {}

    public function handle(OpeningBalanceMigration $migration, User $actor): OpeningBalanceMigration
    {
        Gate::forUser($actor)->authorize('import', $migration);

        return DB::transaction(function () use ($migration, $actor): OpeningBalanceMigration {
            $migration = OpeningBalanceMigration::query()->with('rows')->whereKey($migration)->lockForUpdate()->firstOrFail();
            if ($migration->status === OpeningBalanceMigrationStatus::Imported) {
                return $migration;
            }
            if ($migration->status !== OpeningBalanceMigrationStatus::Validated) {
                throw ValidationException::withMessages(['status' => 'Only a successfully validated dry run may be imported.']);
            }
            if (in_array((int) $actor->getKey(), [(int) $migration->prepared_by_id, (int) $migration->validated_by_id], true)) {
                throw ValidationException::withMessages(['actor' => 'Import requires a third actor independent of preparation and validation.']);
            }
            $batch = OpeningBalanceBatch::create([
                'company_id' => $migration->company_id,
                'financial_year_id' => $migration->financial_year_id,
                'financial_period_id' => $migration->financial_period_id,
                'opening_date' => $migration->opening_date,
                'source_name' => $migration->source_filename,
                'idempotency_key' => $migration->idempotency_key,
                'status' => OpeningBalanceStatus::Draft,
                'prepared_by_id' => $migration->prepared_by_id,
                'notes' => "Controlled migration checksum: {$migration->source_checksum}",
            ]);
            foreach ($migration->rows as $row) {
                OpeningBalanceLine::create([
                    'opening_balance_batch_id' => $batch->getKey(),
                    'company_id' => $migration->company_id,
                    'line_number' => $row->source_row_number - 1,
                    'account_id' => $row->account_id,
                    'description' => $row->description,
                    'debit' => $row->debit,
                    'credit' => $row->credit,
                    'party_id' => $row->party_id,
                    'project_id' => $row->project_id,
                    'cost_center_id' => $row->cost_center_id,
                ]);
            }
            $this->validateBatch->handle($batch, User::findOrFail($migration->validated_by_id));
            $journal = $this->postBatch->handle($batch, $actor);
            if (bccomp((string) $journal->debit_total, (string) $migration->source_debit_total, 4) !== 0
                || bccomp((string) $journal->credit_total, (string) $migration->source_credit_total, 4) !== 0) {
                throw ValidationException::withMessages(['reconciliation' => 'Imported journal does not reconcile to approved source totals.']);
            }
            $migration->update([
                'status' => OpeningBalanceMigrationStatus::Imported,
                'imported_by_id' => $actor->getKey(),
                'imported_at' => now(),
                'opening_balance_batch_id' => $batch->getKey(),
            ]);
            activity('opening_balance_migrations')->causedBy($actor)->performedOn($migration)->event('imported')
                ->withProperties(['opening_balance_batch_id' => $batch->getKey(), 'journal_entry_id' => $journal->getKey()])
                ->log('imported reconciled opening balances');

            return $migration->refresh();
        }, attempts: 3);
    }
}
