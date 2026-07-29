<?php

namespace App\Actions\HR;

use App\Enums\HrDataMigrationStatus;
use App\Models\HrDataMigration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ValidateHrDataMigrationAction
{
    public function handle(HrDataMigration $migration, User $actor): HrDataMigration
    {
        Gate::forUser($actor)->authorize('validate', $migration);

        return DB::transaction(function () use ($migration, $actor): HrDataMigration {
            $migration = HrDataMigration::query()->with('rows')
                ->whereKey($migration)->lockForUpdate()->firstOrFail();
            if ($migration->status !== HrDataMigrationStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only a draft migration may be validated.']);
            }
            if ((int) $migration->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['actor' => 'The preparer cannot validate the same migration.']);
            }

            $errors = [];
            if ($migration->row_count !== $migration->valid_row_count) {
                $errors[] = 'Every source row must resolve without errors.';
            }
            if ($migration->source_path === null || ! Storage::disk('local')->exists($migration->source_path)) {
                $errors[] = 'The private source file is missing.';
            } elseif (hash('sha256', Storage::disk('local')->get($migration->source_path)) !== $migration->source_checksum) {
                $errors[] = 'The private source file checksum no longer matches.';
            }
            if ($migration->rows->count() !== $migration->row_count
                || $migration->rows->contains(fn ($row): bool => $row->validation_errors !== null)) {
                $errors[] = 'Stored row evidence does not reconcile to the validated source.';
            }

            $migration->update([
                'status' => $errors === [] ? HrDataMigrationStatus::Validated : HrDataMigrationStatus::Failed,
                'validated_by_id' => $actor->getKey(),
                'validated_at' => now(),
                'validation_summary' => [
                    ...($migration->validation_summary ?? []),
                    'errors' => $errors,
                    'source_checksum_verified' => $errors === [],
                ],
            ]);

            activity('hr_data_migrations')->causedBy($actor)->performedOn($migration)
                ->event($errors === [] ? 'validated' : 'validation-failed')
                ->withProperties(['company_id' => $migration->company_id, 'errors' => $errors])
                ->log('validated HR data migration dry run');

            return $migration->refresh();
        }, 3);
    }
}
