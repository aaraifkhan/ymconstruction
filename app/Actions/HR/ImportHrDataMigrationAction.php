<?php

namespace App\Actions\HR;

use App\Enums\AttendanceSummaryStatus;
use App\Enums\EmployeeAssetCustodyStatus;
use App\Enums\EmployeeFinancingStatus;
use App\Enums\EmployeeFinancingType;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\HrDataMigrationStatus;
use App\Enums\HrDataMigrationType;
use App\Enums\LeaveLedgerEntryType;
use App\Models\AttendanceMonthlySummary;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Employee;
use App\Models\EmployeeAssetCustody;
use App\Models\EmployeeAssetCustodyEvent;
use App\Models\EmployeeFinancing;
use App\Models\Employment;
use App\Models\FixedAsset;
use App\Models\HrDataMigration;
use App\Models\HrDataMigrationRow;
use App\Models\LeaveLedgerEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportHrDataMigrationAction
{
    public function __construct(private BuildEmployeeFinancingScheduleAction $buildFinancingSchedule) {}

    public function handle(HrDataMigration $migration, User $actor): HrDataMigration
    {
        Gate::forUser($actor)->authorize('import', $migration);

        return DB::transaction(function () use ($migration, $actor): HrDataMigration {
            $migration = HrDataMigration::query()->with('rows')
                ->whereKey($migration)->lockForUpdate()->firstOrFail();
            if ($migration->status === HrDataMigrationStatus::Imported) {
                return $migration;
            }
            if ($migration->status !== HrDataMigrationStatus::Validated) {
                throw ValidationException::withMessages(['status' => 'Only a validated HR migration may be imported.']);
            }
            if (in_array((int) $actor->getKey(), [
                (int) $migration->prepared_by_id,
                (int) $migration->validated_by_id,
            ], true)) {
                throw ValidationException::withMessages([
                    'actor' => 'The importer must be independent from the preparer and validator.',
                ]);
            }

            $records = [];
            foreach ($migration->rows as $row) {
                $records[$row->source_key] = $this->importRow($migration, $row, $actor);
            }
            $this->applyDeferredRelationships($migration, $records);

            foreach ($migration->rows()->get() as $row) {
                $record = $records[$row->source_key];
                $row->update([
                    'imported_record_type' => $record::class,
                    'imported_record_id' => $record->getKey(),
                    'imported_record_checksum' => $this->recordChecksum($migration->type, $record),
                ]);
            }

            $totals = $this->importedTotals($migration);
            if ($this->canonicalTotals($totals) !== $this->canonicalTotals($migration->source_totals ?? [])) {
                throw ValidationException::withMessages([
                    'reconciliation' => 'Imported totals do not reconcile to the approved source.',
                ]);
            }

            $migration->update([
                'status' => HrDataMigrationStatus::Imported,
                'imported_row_count' => count($records),
                'imported_totals' => $totals,
                'imported_by_id' => $actor->getKey(),
                'imported_at' => now(),
            ]);

            activity('hr_data_migrations')->causedBy($actor)->performedOn($migration)
                ->event('imported')->withProperties([
                    'company_id' => $migration->company_id,
                    'type' => $migration->type->value,
                    'source_checksum' => $migration->source_checksum,
                    'source_totals' => $migration->source_totals,
                    'imported_totals' => $totals,
                ])->log('imported approved HR source');

            return $migration->refresh();
        }, 3);
    }

    private function importRow(
        HrDataMigration $migration,
        HrDataMigrationRow $row,
        User $actor,
    ): Model {
        return match ($migration->type) {
            HrDataMigrationType::Departments => $this->importDepartment($migration, $row),
            HrDataMigrationType::Employees => $this->importEmployee($migration, $row),
            HrDataMigrationType::DocumentMetadata => $this->importDocument($migration, $row),
            HrDataMigrationType::LeaveBalances => $this->importLeaveBalance($migration, $row, $actor),
            HrDataMigrationType::Financings => $this->importFinancing($migration, $row, $actor),
            HrDataMigrationType::AssetCustody => $this->importCustody($migration, $row, $actor),
            HrDataMigrationType::HistoricalAttendance => $this->importAttendance($migration, $row, $actor),
        };
    }

    private function importDepartment(HrDataMigration $migration, HrDataMigrationRow $row): Department
    {
        $data = $row->safe_row_data;

        return Department::create([
            'company_id' => $migration->company_id,
            'code' => Str::upper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'is_active' => $data['is_active'] === '1',
        ]);
    }

    private function importEmployee(HrDataMigration $migration, HrDataMigrationRow $row): Employment
    {
        $data = $row->safe_row_data;
        $references = $row->resolved_references ?? [];
        $employee = Employee::create([
            'full_name' => $data['full_name'],
            'is_active' => true,
        ]);

        return Employment::create([
            'company_id' => $migration->company_id,
            'employee_id' => $employee->getKey(),
            'employee_code' => Str::upper($data['employee_code']),
            'joining_date' => $data['joining_date'],
            'ending_date' => $data['ending_date'] ?: null,
            'department_id' => $references['department_id'] ?? null,
            'designation_id' => $references['designation_id'] ?? null,
            'work_location_id' => $references['work_location_id'] ?? null,
            'reporting_to_employment_id' => $references['reporting_to_employment_id'] ?? null,
            'employment_type' => EmploymentType::from($data['employment_type']),
            'employment_status' => EmploymentStatus::from($data['employment_status']),
            'probation_start_date' => $data['probation_start'] ?: null,
            'probation_end_date' => $data['probation_end'] ?: null,
            'confirmation_date' => $data['confirmation_date'] ?: null,
            'notice_period_days' => $data['notice_period_days'] !== '' ? (int) $data['notice_period_days'] : null,
        ]);
    }

    private function importDocument(HrDataMigration $migration, HrDataMigrationRow $row): Document
    {
        $data = $row->safe_row_data;
        $references = $row->resolved_references;
        $type = $migration->company->hrDocumentTypes()->findOrFail($references['hr_document_type_id']);
        $category = DocumentCategory::query()->whereBelongsTo($migration->company)
            ->where('name', 'Employee Document')->firstOrFail();
        $documentableType = $data['scope'] === 'employee' ? Employee::class : Employment::class;
        $documentableId = $data['scope'] === 'employee'
            ? $references['employee_id']
            : $references['employment_id'];

        return Document::create([
            'company_id' => $migration->company_id,
            'document_category_id' => $category->getKey(),
            'hr_document_type_id' => $type->getKey(),
            'documentable_type' => $documentableType,
            'documentable_id' => $documentableId,
            'title' => $data['title'],
            'reference_number' => $data['reference_number'] ?: null,
            'classification' => $type->default_classification,
            'issue_date' => $data['issue_date'] ?: null,
            'expiry_date' => $data['expiry_date'] ?: null,
            'description' => $data['description'] ?: null,
            'metadata' => [
                'migration_id' => $migration->getKey(),
                'source_checksum' => $row->row_checksum,
                'file_attached' => false,
            ],
        ]);
    }

    private function importLeaveBalance(
        HrDataMigration $migration,
        HrDataMigrationRow $row,
        User $actor,
    ): LeaveLedgerEntry {
        $data = $row->safe_row_data;
        $references = $row->resolved_references;

        return LeaveLedgerEntry::create([
            'company_id' => $migration->company_id,
            'employment_id' => $references['employment_id'],
            'leave_type_id' => $references['leave_type_id'],
            'entry_type' => LeaveLedgerEntryType::Opening,
            'effective_on' => $data['as_of_date'],
            'units' => $data['opening_units'],
            'source_type' => HrDataMigrationRow::class,
            'source_id' => $row->getKey(),
            'reason' => 'Approved opening source: '.$data['source_reference'],
            'recorded_by_id' => $actor->getKey(),
        ]);
    }

    private function importFinancing(
        HrDataMigration $migration,
        HrDataMigrationRow $row,
        User $actor,
    ): EmployeeFinancing {
        $data = $row->safe_row_data;
        $references = $row->resolved_references;
        $financing = EmployeeFinancing::create([
            'company_id' => $migration->company_id,
            'employment_id' => $references['employment_id'],
            'reference_number' => 'HRM-'.$migration->getKey().'-'.$row->source_row_number,
            'type' => EmployeeFinancingType::from($data['type']),
            'status' => EmployeeFinancingStatus::Approved,
            'request_date' => $data['request_date'],
            'purpose' => 'Approved historical import: '.$data['approved_source_reference'],
            'principal_amount' => $data['principal'],
            'finance_charge' => $data['finance_charge'],
            'total_repayable' => $references['total_repayable'],
            'installment_count' => (int) $data['installment_count'],
            'first_due_date' => $data['first_due_date'],
            'requested_by_id' => $migration->prepared_by_id,
            'submitted_by_id' => $migration->validated_by_id,
            'submitted_at' => $migration->validated_at,
            'approved_by_id' => $actor->getKey(),
            'approved_at' => now(),
        ]);
        $this->buildFinancingSchedule->handle(
            $financing,
            $data['principal'],
            $data['finance_charge'],
            (int) $data['installment_count'],
            CarbonImmutable::parse($data['first_due_date']),
            1,
        );

        return $financing;
    }

    private function importCustody(
        HrDataMigration $migration,
        HrDataMigrationRow $row,
        User $actor,
    ): EmployeeAssetCustody {
        $data = $row->safe_row_data;
        $references = $row->resolved_references;
        $custody = EmployeeAssetCustody::create([
            'company_id' => $migration->company_id,
            'fixed_asset_id' => $references['fixed_asset_id'],
            'employment_id' => $references['employment_id'],
            'reference_number' => 'HRM-'.$migration->getKey().'-'.$row->source_row_number,
            'status' => EmployeeAssetCustodyStatus::Issued,
            'issued_on' => $data['issued_on'],
            'issued_condition' => $data['issued_condition'],
            'issued_location' => $data['issued_location'] ?: null,
            'issue_notes' => 'Approved historical source: '.$data['source_reference'],
            'prepared_by_id' => $migration->prepared_by_id,
            'issued_by_id' => $actor->getKey(),
            'issued_at' => now(),
        ]);
        EmployeeAssetCustodyEvent::create([
            'company_id' => $migration->company_id,
            'employee_asset_custody_id' => $custody->getKey(),
            'fixed_asset_id' => $references['fixed_asset_id'],
            'employment_id' => $references['employment_id'],
            'event_type' => 'issued',
            'effective_on' => $data['issued_on'],
            'snapshot' => [
                'migration_id' => $migration->getKey(),
                'source_checksum' => $row->row_checksum,
                'condition' => $data['issued_condition'],
                'location' => $data['issued_location'],
            ],
            'reason' => $data['source_reference'],
            'actor_id' => $actor->getKey(),
        ]);
        FixedAsset::query()->whereKey($references['fixed_asset_id'])->update([
            'custodian_employment_id' => $references['employment_id'],
        ]);

        return $custody;
    }

    private function importAttendance(
        HrDataMigration $migration,
        HrDataMigrationRow $row,
        User $actor,
    ): AttendanceMonthlySummary {
        $data = $row->safe_row_data;

        return AttendanceMonthlySummary::create([
            'company_id' => $migration->company_id,
            'employment_id' => $row->resolved_references['employment_id'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'status' => AttendanceSummaryStatus::Finalized,
            'scheduled_days' => (int) $data['scheduled_days'],
            'present_days' => (int) $data['present_days'],
            'absent_days' => (int) $data['absent_days'],
            'half_days' => (int) $data['half_days'],
            'leave_days' => (int) $data['leave_days'],
            'late_minutes' => (int) $data['late_minutes'],
            'overtime_minutes' => (int) $data['overtime_minutes'],
            'unpaid_leave_units' => $data['unpaid_leave_units'],
            'source_checksum' => hash('sha256', $row->row_checksum.'|'.$data['source_reference']),
            'finalized_by_id' => $actor->getKey(),
            'finalized_at' => now(),
        ]);
    }

    /** @param array<string, Model> $records */
    private function applyDeferredRelationships(HrDataMigration $migration, array $records): void
    {
        if ($migration->type === HrDataMigrationType::Departments) {
            foreach ($migration->rows as $row) {
                $parentCode = Str::upper($row->safe_row_data['parent_code']);
                if ($parentCode !== '' && isset($records[$parentCode])) {
                    $records[$row->source_key]->update(['parent_department_id' => $records[$parentCode]->getKey()]);
                }
            }
        }
        if ($migration->type === HrDataMigrationType::Employees) {
            foreach ($migration->rows as $row) {
                $managerCode = Str::upper($row->safe_row_data['reporting_manager_employee_code']);
                if ($managerCode !== '' && isset($records[$managerCode])) {
                    $records[$row->source_key]->update([
                        'reporting_to_employment_id' => $records[$managerCode]->getKey(),
                    ]);
                }
            }
        }
    }

    /** @return array<string, int|string> */
    private function importedTotals(HrDataMigration $migration): array
    {
        $totals = ['rows' => $migration->rows()->whereNotNull('imported_record_id')->count()];
        $fields = match ($migration->type) {
            HrDataMigrationType::LeaveBalances => ['opening_units'],
            HrDataMigrationType::Financings => ['principal', 'finance_charge'],
            HrDataMigrationType::HistoricalAttendance => [
                'scheduled_days', 'present_days', 'absent_days', 'half_days', 'leave_days',
                'late_minutes', 'overtime_minutes', 'unpaid_leave_units',
            ],
            default => [],
        };
        foreach ($fields as $field) {
            $totals[$field] = $migration->rows()->get()->reduce(
                fn (string $carry, HrDataMigrationRow $row): string => bcadd(
                    $carry,
                    (string) ($row->safe_row_data[$field] ?? 0),
                    4,
                ),
                '0.0000',
            );
        }

        return $totals;
    }

    /** @return array<string, string> */
    private function canonicalTotals(array $totals): array
    {
        ksort($totals);

        return array_map(
            fn ($value): string => is_numeric($value) ? number_format((float) $value, 4, '.', '') : (string) $value,
            $totals,
        );
    }

    public function recordChecksum(HrDataMigrationType $type, Model $record): string
    {
        $data = match ($type) {
            HrDataMigrationType::Departments => $record->only([
                'company_id', 'code', 'name', 'parent_department_id', 'description', 'is_active',
            ]),
            HrDataMigrationType::Employees => [
                ...$record->only([
                    'company_id', 'employee_id', 'employee_code', 'joining_date', 'ending_date',
                    'department_id', 'designation_id', 'reporting_to_employment_id',
                    'employment_type', 'employment_status', 'probation_start_date',
                    'probation_end_date', 'confirmation_date', 'notice_period_days', 'work_location_id',
                ]),
                'full_name' => $record->employee->full_name,
            ],
            HrDataMigrationType::DocumentMetadata => $record->only([
                'company_id', 'document_category_id', 'hr_document_type_id',
                'documentable_type', 'documentable_id', 'title', 'reference_number',
                'classification', 'issue_date', 'expiry_date', 'description', 'metadata',
            ]),
            HrDataMigrationType::LeaveBalances => $record->only([
                'company_id', 'employment_id', 'leave_type_id', 'entry_type', 'effective_on',
                'units', 'source_type', 'source_id', 'reason', 'recorded_by_id',
            ]),
            HrDataMigrationType::Financings => [
                ...$record->only([
                    'company_id', 'employment_id', 'reference_number', 'type', 'status',
                    'request_date', 'purpose', 'principal_amount', 'finance_charge',
                    'total_repayable', 'installment_count', 'first_due_date',
                ]),
                'schedule' => $record->installments()->orderBy('installment_number')->get([
                    'installment_number', 'due_date', 'principal_due', 'finance_charge_due', 'total_due',
                ])->toArray(),
            ],
            HrDataMigrationType::AssetCustody => $record->only([
                'company_id', 'fixed_asset_id', 'employment_id', 'reference_number',
                'status', 'issued_on', 'issued_condition', 'issued_location', 'issued_by_id',
            ]),
            HrDataMigrationType::HistoricalAttendance => $record->only([
                'company_id', 'employment_id', 'period_start', 'period_end', 'status',
                'scheduled_days', 'present_days', 'absent_days', 'half_days', 'leave_days',
                'late_minutes', 'overtime_minutes', 'unpaid_leave_units', 'source_checksum',
            ]),
        };

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
