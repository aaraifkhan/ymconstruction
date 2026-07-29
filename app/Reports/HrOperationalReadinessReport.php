<?php

namespace App\Reports;

use App\Enums\HrDataMigrationStatus;
use App\Models\Company;
use App\Models\EmployeeFinancing;
use App\Models\Employment;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrOperationalReadinessReport
{
    public function __construct(
        private PayrollReconciliationReport $payrollReconciliation,
        private FinalSettlementReport $finalSettlementReport,
    ) {}

    /** @return array<string, mixed> */
    public function forCompany(Company $company, User $actor): array
    {
        if (! ($actor->hasRole('super_admin') || $actor->can('View:HrOperationalReadiness'))
            || ! $actor->canAccessTenant($company)) {
            throw ValidationException::withMessages([
                'authorization' => 'You are not authorized to view HR operational readiness.',
            ]);
        }

        $employmentCount = Employment::query()->whereBelongsTo($company)->count();
        $payroll = $this->payrollReconciliation->forCompany($company);
        $settlements = $this->finalSettlementReport->forCompany($company);
        $integrity = $this->integrity($company);
        $configuration = [
            'employee_code_sequence' => $company->employeeCodeSequence()->exists(),
            'attendance_calendar' => $employmentCount === 0 || $company->workCalendars()->where('is_active', true)->exists(),
            'attendance_shift' => $employmentCount === 0 || $company->workShifts()->where('is_active', true)->exists(),
            'attendance_rule' => $employmentCount === 0 || $company->attendanceRules()->where('is_active', true)->exists(),
            'leave_types' => $employmentCount === 0 || $company->leaveTypes()->where('is_active', true)->exists(),
            'payroll_calculation_rule' => $employmentCount === 0 || $company->payrollCalculationRules()->where('is_active', true)->exists(),
            'payroll_account_mappings' => PayrollRun::query()->whereBelongsTo($company)->doesntExist()
                || $company->payrollAccountMappings()->where('is_active', true)->exists(),
        ];
        $migrations = [
            'failed' => $company->hrDataMigrations()->where('status', HrDataMigrationStatus::Failed)->count(),
            'validated_not_imported' => $company->hrDataMigrations()->where('status', HrDataMigrationStatus::Validated)->count(),
            'imported' => $company->hrDataMigrations()->where('status', HrDataMigrationStatus::Imported)->count(),
        ];
        $reconciliation = [
            'payroll_runs' => $payroll->count(),
            'payroll_gl_passes' => $payroll->every('reconciled'),
            'final_settlements' => $settlements->count(),
            'final_settlement_gl_passes' => $settlements->every('operational_gl_reconciled'),
            'final_settlement_treasury_passes' => $settlements->every('treasury_reconciled'),
            'financing_schedule_passes' => $this->financingSchedulesReconcile($company),
        ];
        $device = [
            'normalized_csv_fallback_available' => true,
            'configured_devices' => $company->attendanceDevices()->count(),
            'device_specific_connector_verified' => false,
            'historical_machine_backfill_available' => false,
            'blocker' => 'HR-5 requires actual machine identity, protocol, topology, fixtures, clock behavior, and credentials.',
        ];

        $blockers = collect([
            ...collect($configuration)->filter(fn (bool $passes): bool => ! $passes)
                ->keys()->map(fn (string $key): string => "Configuration gate failed: {$key}."),
            ...collect($integrity)->except('passes')->filter(fn ($value): bool => $value instanceof Collection && $value->isNotEmpty())
                ->keys()->map(fn (string $key): string => "Integrity gate failed: {$key}."),
            ...collect($reconciliation)->filter(fn ($value, string $key): bool => str_ends_with($key, '_passes') && ! $value)
                ->keys()->map(fn (string $key): string => "Reconciliation gate failed: {$key}."),
            ...($migrations['failed'] > 0 ? ['One or more HR migration dry runs failed validation.'] : []),
            ...($migrations['validated_not_imported'] > 0 ? ['One or more validated HR sources still await independent import.'] : []),
            $device['blocker'],
        ])->values();

        return [
            'company_id' => $company->getKey(),
            'configuration' => $configuration,
            'migrations' => $migrations,
            'integrity' => $integrity,
            'reconciliation' => $reconciliation,
            'device_offline_continuity' => $device,
            'pilot_ready_except_device_connector' => $blockers->count() === 1,
            'rollout_blockers' => $blockers,
        ];
    }

    /** @return array<string, mixed> */
    private function integrity(Company $company): array
    {
        $duplicateCodes = Employment::query()->whereBelongsTo($company)
            ->select('employee_code')->groupBy('employee_code')->havingRaw('COUNT(*) > 1')
            ->pluck('employee_code');
        $crossCompanyDepartments = Employment::query()
            ->join('departments', 'departments.id', '=', 'employments.department_id')
            ->where('employments.company_id', $company->getKey())
            ->whereColumn('departments.company_id', '<>', 'employments.company_id')
            ->pluck('employments.id');
        $crossCompanyAttendance = DB::table('attendance_monthly_summaries')
            ->join('employments', 'employments.id', '=', 'attendance_monthly_summaries.employment_id')
            ->where('attendance_monthly_summaries.company_id', $company->getKey())
            ->whereColumn('employments.company_id', '<>', 'attendance_monthly_summaries.company_id')
            ->pluck('attendance_monthly_summaries.id');
        $duplicateRawEvents = DB::table('attendance_raw_events')
            ->where('company_id', $company->getKey())
            ->select('attendance_device_id', 'event_fingerprint')
            ->groupBy('attendance_device_id', 'event_fingerprint')
            ->havingRaw('COUNT(*) > 1')->get();

        $collections = collect([
            'duplicate_employee_codes' => $duplicateCodes,
            'cross_company_departments' => $crossCompanyDepartments,
            'cross_company_attendance' => $crossCompanyAttendance,
            'duplicate_raw_events' => $duplicateRawEvents,
        ]);

        return [...$collections->all(), 'passes' => $collections->every->isEmpty()];
    }

    private function financingSchedulesReconcile(Company $company): bool
    {
        return EmployeeFinancing::query()->whereBelongsTo($company)
            ->with('installments')->get()
            ->every(fn (EmployeeFinancing $financing): bool => bccomp(
                (string) $financing->total_repayable,
                $financing->installments->reduce(
                    fn (string $total, $installment): string => bcadd($total, (string) $installment->total_due, 4),
                    '0.0000',
                ),
                4,
            ) === 0);
    }
}
