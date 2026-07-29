<?php

namespace App\Actions\HR;

use App\Enums\HrDataMigrationStatus;
use App\Enums\HrDataMigrationType;
use App\Models\EmployeeFinancing;
use App\Models\Employment;
use App\Models\HrDataMigration;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RollbackHrDataMigrationAction
{
    public function __construct(private ImportHrDataMigrationAction $importAction) {}

    public function handle(HrDataMigration $migration, User $actor, string $reason): HrDataMigration
    {
        Gate::forUser($actor)->authorize('rollback', $migration);
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Provide a rollback reason of at least 10 characters.']);
        }

        return DB::transaction(function () use ($migration, $actor, $reason): HrDataMigration {
            $migration = HrDataMigration::query()->with('rows')
                ->whereKey($migration)->lockForUpdate()->firstOrFail();
            if ($migration->status === HrDataMigrationStatus::RolledBack) {
                return $migration;
            }
            if ($migration->status !== HrDataMigrationStatus::Imported) {
                throw ValidationException::withMessages(['status' => 'Only an imported HR migration may be rolled back.']);
            }

            $records = [];
            foreach ($migration->rows as $row) {
                /** @var class-string<Model> $model */
                $model = $row->imported_record_type;
                $record = $model::query()->withTrashed()->find($row->imported_record_id);
                if ($record === null
                    || $this->importAction->recordChecksum($migration->type, $record) !== $row->imported_record_checksum) {
                    throw ValidationException::withMessages([
                        'rollback' => "Imported row {$row->source_row_number} changed after import and cannot be rolled back.",
                    ]);
                }
                $records[] = $record;
            }

            $this->assertNoDownstreamUse($migration, $records);
            $this->removeImportedRecords($migration, $records);

            $migration->update([
                'status' => HrDataMigrationStatus::RolledBack,
                'rolled_back_by_id' => $actor->getKey(),
                'rolled_back_at' => now(),
                'rollback_reason' => trim($reason),
            ]);
            activity('hr_data_migrations')->causedBy($actor)->performedOn($migration)
                ->event('rolled-back')->withProperties([
                    'company_id' => $migration->company_id,
                    'type' => $migration->type->value,
                    'source_checksum' => $migration->source_checksum,
                    'reason' => trim($reason),
                    'row_count' => $migration->row_count,
                ])->log('rolled back HR data migration');

            return $migration->refresh();
        }, 3);
    }

    /** @param list<Model> $records */
    private function assertNoDownstreamUse(HrDataMigration $migration, array $records): void
    {
        foreach ($records as $record) {
            $used = match ($migration->type) {
                HrDataMigrationType::Departments => $record->employments()->exists()
                    || $record->childDepartments()->whereNotIn(
                        'id',
                        collect($records)->pluck('id'),
                    )->exists(),
                HrDataMigrationType::Employees => $this->employmentHasDownstreamUse($record),
                HrDataMigrationType::DocumentMetadata => $record->versions()->exists(),
                HrDataMigrationType::LeaveBalances => false,
                HrDataMigrationType::Financings => $record->transactions()->exists()
                    || $record->treasuryTransactions()->exists()
                    || PayrollEntryComponent::query()->whereMorphedTo('source', $record)->exists(),
                HrDataMigrationType::AssetCustody => $record->events()->count() !== 1,
                HrDataMigrationType::HistoricalAttendance => PayrollEntryComponent::query()
                    ->whereMorphedTo('source', $record)->exists(),
            };
            if ($used) {
                throw ValidationException::withMessages([
                    'rollback' => 'Imported records have downstream workflow evidence; reverse that workflow before migration rollback.',
                ]);
            }
        }
    }

    private function employmentHasDownstreamUse(Employment $employment): bool
    {
        return $employment->documents()->exists()
            || $employment->compensations()->exists()
            || PayrollEntry::query()->whereBelongsTo($employment)->exists()
            || $employment->attendanceMonthlySummaries()->exists()
            || $employment->employeeFinancings()->exists()
            || $employment->assetCustodies()->exists()
            || $employment->directReports()->whereNotNull('id')->exists();
    }

    /** @param list<Model> $records */
    private function removeImportedRecords(HrDataMigration $migration, array $records): void
    {
        match ($migration->type) {
            HrDataMigrationType::Departments => $this->removeDepartments($records),
            HrDataMigrationType::Employees => $this->removeEmployees($records),
            HrDataMigrationType::DocumentMetadata => $this->deleteRows('documents', $records),
            HrDataMigrationType::LeaveBalances => $this->deleteRows('leave_ledger_entries', $records),
            HrDataMigrationType::Financings => $this->removeFinancings($records),
            HrDataMigrationType::AssetCustody => $this->removeCustodies($records),
            HrDataMigrationType::HistoricalAttendance => $this->deleteRows('attendance_monthly_summaries', $records),
        };
    }

    /** @param list<Model> $records */
    private function removeDepartments(array $records): void
    {
        $ids = collect($records)->pluck('id');
        DB::table('departments')->whereIn('id', $ids)->update(['parent_department_id' => null]);
        DB::table('departments')->whereIn('id', $ids)->delete();
    }

    /** @param list<Employment> $records */
    private function removeEmployees(array $records): void
    {
        $employmentIds = collect($records)->pluck('id');
        $employeeIds = collect($records)->pluck('employee_id');
        DB::table('employment_changes')->whereIn('employment_id', $employmentIds)->delete();
        DB::table('employments')->whereIn('id', $employmentIds)->delete();
        DB::table('employees')->whereIn('id', $employeeIds)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('employments')
                ->whereColumn('employments.employee_id', 'employees.id'))
            ->delete();
    }

    /** @param list<EmployeeFinancing> $records */
    private function removeFinancings(array $records): void
    {
        $ids = collect($records)->pluck('id');
        DB::table('employee_financing_installments')->whereIn('employee_financing_id', $ids)->delete();
        DB::table('employee_financings')->whereIn('id', $ids)->delete();
    }

    /** @param list<Model> $records */
    private function removeCustodies(array $records): void
    {
        foreach ($records as $custody) {
            DB::table('fixed_assets')->where('id', $custody->fixed_asset_id)
                ->where('custodian_employment_id', $custody->employment_id)
                ->update(['custodian_employment_id' => null]);
        }
        $ids = collect($records)->pluck('id');
        DB::table('employee_asset_custody_events')->whereIn('employee_asset_custody_id', $ids)->delete();
        DB::table('employee_asset_custodies')->whereIn('id', $ids)->delete();
    }

    /** @param list<Model> $records */
    private function deleteRows(string $table, array $records): void
    {
        DB::table($table)->whereIn('id', collect($records)->pluck('id'))->delete();
    }
}
