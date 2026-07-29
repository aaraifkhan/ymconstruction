<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingType;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\HrDataMigrationStatus;
use App\Enums\HrDataMigrationType;
use App\Enums\HrDocumentApplicability;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employment;
use App\Models\FixedAsset;
use App\Models\HrDataMigration;
use App\Models\HrDataMigrationRow;
use App\Models\HrDocumentType;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrepareHrDataMigrationAction
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const MAX_ROWS = 10000;

    public function handle(
        Company $company,
        HrDataMigrationType $type,
        User $actor,
        string $filename,
        string $csv,
    ): HrDataMigration {
        if (! ($actor->hasRole('super_admin') || $actor->can('Create:HrDataMigration'))
            || ! $actor->canAccessTenant($company)) {
            throw ValidationException::withMessages([
                'authorization' => 'You are not authorized to prepare this HR migration.',
            ]);
        }
        if (strlen($csv) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => 'HR migration CSV must not exceed 10 MB.']);
        }

        $records = $this->parse($type, $csv);
        if ($records === []) {
            throw ValidationException::withMessages(['file' => 'The migration file has no data rows.']);
        }
        if (count($records) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'HR migration CSV must not exceed 10,000 rows.']);
        }

        $checksum = hash('sha256', $csv);
        $existing = HrDataMigration::query()
            ->whereBelongsTo($company)
            ->where('type', $type)
            ->where('source_checksum', $checksum)
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        $idempotencyKey = (string) Str::uuid();
        $path = "hr-migrations/{$company->getKey()}/{$idempotencyKey}/source.csv";
        Storage::disk('local')->put($path, $csv);

        try {
            return DB::transaction(function () use (
                $company, $type, $actor, $filename, $checksum, $idempotencyKey, $path, $records,
            ): HrDataMigration {
                $migration = HrDataMigration::create([
                    'company_id' => $company->getKey(),
                    'type' => $type,
                    'idempotency_key' => $idempotencyKey,
                    'source_filename' => basename($filename),
                    'source_path' => $path,
                    'source_checksum' => $checksum,
                    'status' => HrDataMigrationStatus::Draft,
                    'row_count' => count($records),
                    'source_totals' => $this->totals($type, $records),
                    'prepared_by_id' => $actor->getKey(),
                ]);

                $batchKeys = collect($records)->map(fn (array $record): string => $this->sourceKey($type, $record));
                $duplicateKeys = $batchKeys->duplicates()->unique()->all();
                $validRows = 0;
                foreach ($records as $index => $record) {
                    $sourceKey = $this->sourceKey($type, $record);
                    $resolved = $this->resolve($company, $type, $record, $batchKeys->all());
                    if (in_array($sourceKey, $duplicateKeys, true)) {
                        $resolved['errors'][] = 'The source key is duplicated inside this file.';
                    }
                    if ($resolved['errors'] === []) {
                        $validRows++;
                    }

                    HrDataMigrationRow::create([
                        'hr_data_migration_id' => $migration->getKey(),
                        'company_id' => $company->getKey(),
                        'source_row_number' => $index + 2,
                        'source_key' => $sourceKey,
                        'row_checksum' => $this->checksum($record),
                        'safe_row_data' => $record,
                        'resolved_references' => $resolved['references'] ?: null,
                        'validation_errors' => $resolved['errors'] ?: null,
                    ]);
                }

                $migration->forceFill([
                    'valid_row_count' => $validRows,
                    'validation_summary' => [
                        'invalid_rows' => count($records) - $validRows,
                        'headers' => $type->headers(),
                    ],
                ])->saveQuietly();

                activity('hr_data_migrations')->causedBy($actor)->performedOn($migration)
                    ->event('dry-run')
                    ->withProperties([
                        'company_id' => $company->getKey(),
                        'type' => $type->value,
                        'source_checksum' => $checksum,
                        'row_count' => count($records),
                        'valid_row_count' => $validRows,
                    ])->log('prepared HR data migration dry run');

                return $migration->refresh();
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    /** @return list<array<string, string>> */
    private function parse(HrDataMigrationType $type, string $csv): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv);
        rewind($stream);
        $headers = fgetcsv($stream);
        if ($headers === false) {
            return [];
        }
        $headers = array_map(
            fn ($header): string => Str::of((string) $header)->trim()->lower()->toString(),
            $headers,
        );
        if ($headers !== $type->headers()) {
            throw ValidationException::withMessages([
                'file' => 'CSV headers must be exactly: '.implode(', ', $type->headers()).'.',
            ]);
        }

        $records = [];
        while (($values = fgetcsv($stream)) !== false) {
            if ($values === [null] || collect($values)->every(fn ($value): bool => blank($value))) {
                continue;
            }
            if (count($values) !== count($headers)) {
                throw ValidationException::withMessages([
                    'file' => 'Every CSV row must contain exactly '.count($headers).' columns.',
                ]);
            }
            $records[] = array_combine(
                $headers,
                array_map(fn ($value): string => trim((string) $value), $values),
            );
        }
        fclose($stream);

        return $records;
    }

    /** @param array<string, string> $record */
    private function sourceKey(HrDataMigrationType $type, array $record): string
    {
        return match ($type) {
            HrDataMigrationType::Departments => Str::upper($record['code']),
            HrDataMigrationType::Employees => Str::upper($record['employee_code']),
            HrDataMigrationType::DocumentMetadata => implode('|', [
                Str::upper($record['employee_code']), $record['scope'],
                $record['document_type_code'], $record['reference_number'] ?: $record['title'],
            ]),
            HrDataMigrationType::LeaveBalances => implode('|', [
                Str::upper($record['employee_code']), Str::upper($record['leave_type_code']),
                $record['as_of_date'], $record['source_reference'],
            ]),
            HrDataMigrationType::Financings => $record['approved_source_reference'],
            HrDataMigrationType::AssetCustody => Str::upper($record['asset_number']),
            HrDataMigrationType::HistoricalAttendance => implode('|', [
                Str::upper($record['employee_code']), $record['period_start'], $record['period_end'],
            ]),
        };
    }

    /**
     * @param  array<string, string>  $record
     * @param  list<string>  $batchKeys
     * @return array{references: array<string, int|string>, errors: list<string>}
     */
    private function resolve(
        Company $company,
        HrDataMigrationType $type,
        array $record,
        array $batchKeys,
    ): array {
        $errors = [];
        $references = [];
        match ($type) {
            HrDataMigrationType::Departments => $this->resolveDepartment($company, $record, $batchKeys, $references, $errors),
            HrDataMigrationType::Employees => $this->resolveEmployee($company, $record, $batchKeys, $references, $errors),
            HrDataMigrationType::DocumentMetadata => $this->resolveDocument($company, $record, $references, $errors),
            HrDataMigrationType::LeaveBalances => $this->resolveLeave($company, $record, $references, $errors),
            HrDataMigrationType::Financings => $this->resolveFinancing($company, $record, $references, $errors),
            HrDataMigrationType::AssetCustody => $this->resolveCustody($company, $record, $references, $errors),
            HrDataMigrationType::HistoricalAttendance => $this->resolveAttendance($company, $record, $references, $errors),
        };

        return compact('references', 'errors');
    }

    /** @param array<string, string> $record */
    private function resolveDepartment(
        Company $company,
        array $record,
        array $batchKeys,
        array &$references,
        array &$errors,
    ): void {
        $code = Str::upper($record['code']);
        $this->required($record, ['code', 'name'], $errors);
        if (Department::query()->whereBelongsTo($company)->where('code', $code)->exists()) {
            $errors[] = 'Department code already exists in this company.';
        }
        if (! in_array($record['is_active'], ['0', '1'], true)) {
            $errors[] = 'is_active must be 0 or 1.';
        }
        $parent = Str::upper($record['parent_code']);
        if ($parent !== '') {
            if ($parent === $code) {
                $errors[] = 'A Department cannot be its own parent.';
            }
            $existingParent = Department::query()->whereBelongsTo($company)->where('code', $parent)->first();
            if ($existingParent === null && ! in_array($parent, $batchKeys, true)) {
                $errors[] = 'Parent Department code was not found in this company or file.';
            }
            if ($existingParent !== null) {
                $references['parent_department_id'] = $existingParent->getKey();
            }
        }
    }

    /** @param array<string, string> $record */
    private function resolveEmployee(
        Company $company,
        array $record,
        array $batchKeys,
        array &$references,
        array &$errors,
    ): void {
        $this->required($record, ['employee_code', 'full_name', 'joining_date', 'employment_type', 'employment_status'], $errors);
        $employeeCode = Str::upper($record['employee_code']);
        if (Employment::query()->whereBelongsTo($company)->where('employee_code', $employeeCode)->exists()) {
            $errors[] = 'Employee code already exists in this company.';
        }
        if (EmploymentType::tryFrom($record['employment_type']) === null) {
            $errors[] = 'Employment type is invalid.';
        }
        $status = EmploymentStatus::tryFrom($record['employment_status']);
        if ($status === null || $status === EmploymentStatus::Ended) {
            $errors[] = 'Employment status is invalid for import.';
        }
        foreach (['joining_date', 'ending_date', 'probation_start', 'probation_end', 'confirmation_date'] as $field) {
            $this->date($record[$field], $field, $errors);
        }
        if ($record['ending_date'] !== '' && $record['joining_date'] !== ''
            && $record['ending_date'] < $record['joining_date']) {
            $errors[] = 'Ending date cannot precede joining date.';
        }
        if (in_array($status, [EmploymentStatus::Resigned, EmploymentStatus::Terminated], true)
            && $record['ending_date'] === '') {
            $errors[] = 'Resigned or Terminated Employment requires an ending date.';
        }
        $this->optionalPositiveInteger($record['notice_period_days'], 'notice_period_days', $errors);
        $this->resolveCode(Department::class, $company, $record['department_code'], 'department_id', $references, $errors);
        $this->resolveCode(Designation::class, $company, $record['designation_code'], 'designation_id', $references, $errors);
        $this->resolveCode(WorkLocation::class, $company, $record['work_location_code'], 'work_location_id', $references, $errors);
        $managerCode = Str::upper($record['reporting_manager_employee_code']);
        if ($managerCode !== '') {
            $manager = Employment::query()->whereBelongsTo($company)->where('employee_code', $managerCode)->first();
            if ($manager !== null) {
                $references['reporting_to_employment_id'] = $manager->getKey();
            } elseif (! in_array($managerCode, $batchKeys, true)) {
                $errors[] = 'Reporting manager Employee code was not found in this company or file.';
            }
            if ($managerCode === $employeeCode) {
                $errors[] = 'An Employment cannot report to itself.';
            }
        }
    }

    /** @param array<string, string> $record */
    private function resolveDocument(Company $company, array $record, array &$references, array &$errors): void
    {
        $this->required($record, ['employee_code', 'scope', 'document_type_code', 'title'], $errors);
        $employment = $this->employment($company, $record['employee_code'], $errors);
        $type = HrDocumentType::query()->whereBelongsTo($company)
            ->where('code', $record['document_type_code'])->where('is_active', true)->first();
        if ($type === null) {
            $errors[] = 'HR document type was not found or is inactive.';
        }
        if (! in_array($record['scope'], ['employee', 'employment'], true)) {
            $errors[] = 'Document scope must be employee or employment.';
        } elseif ($type !== null
            && $type->applicability !== HrDocumentApplicability::from($record['scope'])) {
            $errors[] = 'Document scope does not match the controlled document type.';
        }
        foreach (['issue_date', 'expiry_date'] as $field) {
            $this->date($record[$field], $field, $errors);
        }
        if ($type?->requires_issue_date && $record['issue_date'] === '') {
            $errors[] = 'This document type requires an issue date.';
        }
        if ($type?->requires_expiry && $record['expiry_date'] === '') {
            $errors[] = 'This document type requires an expiry date.';
        }
        if ($employment !== null) {
            $references['employment_id'] = $employment->getKey();
            $references['employee_id'] = $employment->employee_id;
        }
        if ($type !== null) {
            $references['hr_document_type_id'] = $type->getKey();
        }
    }

    /** @param array<string, string> $record */
    private function resolveLeave(Company $company, array $record, array &$references, array &$errors): void
    {
        $this->required($record, ['employee_code', 'leave_type_code', 'as_of_date', 'opening_units', 'source_reference'], $errors);
        $employment = $this->employment($company, $record['employee_code'], $errors);
        $leaveType = LeaveType::query()->whereBelongsTo($company)
            ->where('code', Str::upper($record['leave_type_code']))->where('is_active', true)->first();
        if ($leaveType === null) {
            $errors[] = 'Leave Type code was not found or is inactive.';
        }
        $this->date($record['as_of_date'], 'as_of_date', $errors);
        $this->decimal($record['opening_units'], 'opening_units', false, $errors, 2);
        if ($employment !== null) {
            $references['employment_id'] = $employment->getKey();
        }
        if ($leaveType !== null) {
            $references['leave_type_id'] = $leaveType->getKey();
        }
    }

    /** @param array<string, string> $record */
    private function resolveFinancing(Company $company, array $record, array &$references, array &$errors): void
    {
        $this->required($record, array_keys($record), $errors);
        $employment = $this->employment($company, $record['employee_code'], $errors);
        $type = EmployeeFinancingType::tryFrom($record['type']);
        if ($type === null) {
            $errors[] = 'Financing type must be loan or advance.';
        }
        $this->date($record['request_date'], 'request_date', $errors);
        $this->date($record['first_due_date'], 'first_due_date', $errors);
        $principal = $this->decimal($record['principal'], 'principal', false, $errors);
        $charge = $this->decimal($record['finance_charge'], 'finance_charge', true, $errors);
        $this->optionalPositiveInteger($record['installment_count'], 'installment_count', $errors, false);
        if ($type === EmployeeFinancingType::Advance && bccomp($charge, '0', 4) !== 0) {
            $errors[] = 'Employee Advances cannot carry a finance charge.';
        }
        if ($employment !== null) {
            $references['employment_id'] = $employment->getKey();
        }
        $references['total_repayable'] = bcadd($principal, $charge, 4);
    }

    /** @param array<string, string> $record */
    private function resolveCustody(Company $company, array $record, array &$references, array &$errors): void
    {
        $this->required($record, ['employee_code', 'asset_number', 'issued_on', 'issued_condition', 'source_reference'], $errors);
        $employment = $this->employment($company, $record['employee_code'], $errors);
        $asset = FixedAsset::query()->whereBelongsTo($company)
            ->where('asset_number', Str::upper($record['asset_number']))->first();
        if ($asset === null) {
            $errors[] = 'Fixed Asset number was not found in this company.';
        } elseif ($asset->employeeCustodies()->whereIn('status', ['issued', 'acknowledged', 'return_pending', 'exception'])->exists()) {
            $errors[] = 'Fixed Asset already has a live custodian.';
        }
        $this->date($record['issued_on'], 'issued_on', $errors);
        if ($employment !== null) {
            $references['employment_id'] = $employment->getKey();
        }
        if ($asset !== null) {
            $references['fixed_asset_id'] = $asset->getKey();
        }
    }

    /** @param array<string, string> $record */
    private function resolveAttendance(Company $company, array $record, array &$references, array &$errors): void
    {
        $this->required($record, array_keys($record), $errors);
        $employment = $this->employment($company, $record['employee_code'], $errors);
        foreach (['period_start', 'period_end'] as $field) {
            $this->date($record[$field], $field, $errors);
        }
        if ($record['period_end'] < $record['period_start']) {
            $errors[] = 'Attendance period end cannot precede its start.';
        }
        foreach (['scheduled_days', 'present_days', 'absent_days', 'half_days', 'leave_days', 'late_minutes', 'overtime_minutes'] as $field) {
            if (filter_var($record[$field], FILTER_VALIDATE_INT) === false || (int) $record[$field] < 0) {
                $errors[] = "{$field} must be a non-negative integer.";
            }
        }
        $this->decimal($record['unpaid_leave_units'], 'unpaid_leave_units', true, $errors, 2);
        if ($employment !== null) {
            $references['employment_id'] = $employment->getKey();
            if ($employment->attendanceMonthlySummaries()
                ->whereDate('period_start', $record['period_start'])
                ->whereDate('period_end', $record['period_end'])->exists()) {
                $errors[] = 'An Attendance summary already exists for this Employment and period.';
            }
        }
    }

    /** @param class-string $model */
    private function resolveCode(
        string $model,
        Company $company,
        string $code,
        string $reference,
        array &$references,
        array &$errors,
    ): void {
        if ($code === '') {
            return;
        }
        $record = $model::query()->whereBelongsTo($company)->where('code', Str::upper($code))->first();
        if ($record === null) {
            $errors[] = Str::headline($reference).' code was not found in this company.';
        } else {
            $references[$reference] = $record->getKey();
        }
    }

    private function employment(Company $company, string $employeeCode, array &$errors): ?Employment
    {
        $employment = Employment::query()->whereBelongsTo($company)
            ->where('employee_code', Str::upper($employeeCode))->first();
        if ($employment === null) {
            $errors[] = 'Employee code was not found in this company.';
        }

        return $employment;
    }

    /** @param list<string> $fields */
    private function required(array $record, array $fields, array &$errors): void
    {
        foreach ($fields as $field) {
            if ($record[$field] === '') {
                $errors[] = "{$field} is required.";
            }
        }
    }

    private function date(string $value, string $field, array &$errors): void
    {
        if ($value === '') {
            return;
        }
        try {
            if (CarbonImmutable::createFromFormat('!Y-m-d', $value)->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException;
            }
        } catch (\Throwable) {
            $errors[] = "{$field} must use YYYY-MM-DD.";
        }
    }

    private function optionalPositiveInteger(
        string $value,
        string $field,
        array &$errors,
        bool $optional = true,
    ): void {
        if ($value === '' && $optional) {
            return;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            $errors[] = "{$field} must be a positive integer.";
        }
    }

    private function decimal(
        string $value,
        string $field,
        bool $allowZero,
        array &$errors,
        int $scale = 4,
    ): string {
        $pattern = '/^\d{1,15}(\.\d{1,'.$scale.'})?$/';
        if (! preg_match($pattern, $value)) {
            $errors[] = "{$field} must be a non-negative number with at most {$scale} decimals.";

            return number_format(0, $scale, '.', '');
        }
        $normalized = number_format((float) $value, $scale, '.', '');
        if (! $allowZero && bccomp($normalized, '0', $scale) !== 1) {
            $errors[] = "{$field} must be greater than zero.";
        }

        return $normalized;
    }

    /** @param list<array<string, string>> $records */
    private function totals(HrDataMigrationType $type, array $records): array
    {
        $totals = ['rows' => count($records)];
        $fields = match ($type) {
            HrDataMigrationType::LeaveBalances => ['opening_units'],
            HrDataMigrationType::Financings => ['principal', 'finance_charge'],
            HrDataMigrationType::HistoricalAttendance => [
                'scheduled_days', 'present_days', 'absent_days', 'half_days', 'leave_days',
                'late_minutes', 'overtime_minutes', 'unpaid_leave_units',
            ],
            default => [],
        };
        foreach ($fields as $field) {
            $totals[$field] = collect($records)->reduce(
                fn (string $carry, array $record): string => bcadd($carry, is_numeric($record[$field]) ? $record[$field] : '0', 4),
                '0.0000',
            );
        }

        return $totals;
    }

    /** @param array<string, string> $record */
    private function checksum(array $record): string
    {
        return hash('sha256', json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
