<?php

namespace App\Actions\Accounting;

use App\Enums\OpeningBalanceMigrationStatus;
use App\Models\OpeningBalanceMigration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ValidateOpeningBalanceMigrationAction
{
    public function handle(OpeningBalanceMigration $migration, User $actor): OpeningBalanceMigration
    {
        Gate::forUser($actor)->authorize('validate', $migration);

        return DB::transaction(function () use ($migration, $actor): OpeningBalanceMigration {
            $migration = OpeningBalanceMigration::query()->with('rows')->whereKey($migration)->lockForUpdate()->firstOrFail();
            if ($migration->status !== OpeningBalanceMigrationStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only a draft migration may be validated.']);
            }
            if ((int) $migration->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['actor' => 'The preparer cannot validate the same migration.']);
            }
            $errors = [];
            if ($migration->row_count !== $migration->valid_row_count) {
                $errors[] = 'Every source row must resolve without errors.';
            }
            if (bccomp((string) $migration->source_debit_total, '0.0000', 4) !== 1
                || bccomp((string) $migration->source_debit_total, (string) $migration->source_credit_total, 4) !== 0) {
                $errors[] = 'Source debit and credit totals must balance and be greater than zero.';
            }
            $migration->update([
                'status' => $errors === [] ? OpeningBalanceMigrationStatus::Validated : OpeningBalanceMigrationStatus::Failed,
                'validated_by_id' => $actor->getKey(),
                'validated_at' => now(),
                'validation_summary' => [...($migration->validation_summary ?? []), 'errors' => $errors],
            ]);
            activity('opening_balance_migrations')->causedBy($actor)->performedOn($migration)
                ->event($errors === [] ? 'validated' : 'validation-failed')
                ->withProperties(['errors' => $errors])->log('validated opening-balance migration dry run');

            return $migration->refresh();
        });
    }
}
