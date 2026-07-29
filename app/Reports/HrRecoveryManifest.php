<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrRecoveryManifest
{
    /** @return array<string, mixed> */
    public function generate(Company $company, User $actor): array
    {
        if (! ($actor->hasRole('super_admin') || $actor->can('View:HrRecoveryManifest'))
            || ! $actor->canAccessTenant($company)) {
            throw ValidationException::withMessages([
                'authorization' => 'You are not authorized to generate this HR recovery manifest.',
            ]);
        }

        $tables = [
            'departments', 'designations', 'employees', 'employments', 'documents',
            'document_versions', 'leave_ledger_entries', 'attendance_records',
            'attendance_punches', 'attendance_raw_events', 'attendance_monthly_summaries',
            'employee_financings', 'employee_financing_installments',
            'employee_financing_transactions', 'payroll_runs', 'payroll_entries',
            'payroll_entry_components', 'employee_asset_custodies',
            'employee_asset_custody_events', 'employee_clearances',
            'final_settlements', 'final_settlement_lines', 'hr_data_migrations',
            'hr_data_migration_rows',
        ];
        $tableEvidence = [];
        foreach ($tables as $table) {
            $query = DB::table($table);
            if ($table === 'employees') {
                $query->whereExists(fn ($employment) => $employment->selectRaw('1')
                    ->from('employments')
                    ->whereColumn('employments.employee_id', 'employees.id')
                    ->where('employments.company_id', $company->getKey()));
            } elseif ($table === 'document_versions') {
                $query->whereExists(fn ($document) => $document->selectRaw('1')
                    ->from('documents')
                    ->whereColumn('documents.id', 'document_versions.document_id')
                    ->where('documents.company_id', $company->getKey()));
            } elseif ($table === 'hr_data_migration_rows') {
                $query->where('company_id', $company->getKey());
            } elseif (DB::getSchemaBuilder()->hasColumn($table, 'company_id')) {
                $query->where('company_id', $company->getKey());
            }
            $rows = $query->orderBy('id')->get()
                ->map(fn ($row): array => [
                    'id' => $row->id,
                    'checksum' => hash(
                        'sha256',
                        json_encode((array) $row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    ),
                ])->all();
            $tableEvidence[$table] = [
                'count' => count($rows),
                'checksum' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)),
            ];
        }

        $payload = [
            'version' => 1,
            'company_id' => $company->getKey(),
            'tables' => $tableEvidence,
        ];

        return [...$payload, 'checksum' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR))];
    }

    /**
     * @param  array<string, mixed>  $expected
     * @return array{matches: bool, expected_checksum: string, actual_checksum: string}
     */
    public function verify(Company $company, User $actor, array $expected): array
    {
        $actual = $this->generate($company, $actor);

        return [
            'matches' => isset($expected['checksum'])
                && hash_equals((string) $expected['checksum'], $actual['checksum']),
            'expected_checksum' => (string) ($expected['checksum'] ?? ''),
            'actual_checksum' => $actual['checksum'],
        ];
    }
}
