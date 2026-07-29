<?php

namespace App\Enums;

enum HrDataMigrationType: string
{
    case Departments = 'departments';
    case Employees = 'employees';
    case DocumentMetadata = 'document_metadata';
    case LeaveBalances = 'leave_balances';
    case Financings = 'financings';
    case AssetCustody = 'asset_custody';
    case HistoricalAttendance = 'historical_attendance';

    public function label(): string
    {
        return match ($this) {
            self::Departments => 'Department hierarchy',
            self::Employees => 'Employees and Employments',
            self::DocumentMetadata => 'HR document metadata',
            self::LeaveBalances => 'Leave opening balances',
            self::Financings => 'Employee Loans and Advances',
            self::AssetCustody => 'Fixed Asset custody',
            self::HistoricalAttendance => 'Historical Attendance summaries',
        };
    }

    /** @return list<string> */
    public function headers(): array
    {
        return match ($this) {
            self::Departments => ['code', 'name', 'parent_code', 'description', 'is_active'],
            self::Employees => [
                'employee_code', 'full_name', 'joining_date', 'ending_date', 'employment_type',
                'employment_status', 'department_code', 'designation_code',
                'reporting_manager_employee_code', 'work_location_code', 'probation_start',
                'probation_end', 'confirmation_date', 'notice_period_days',
            ],
            self::DocumentMetadata => [
                'employee_code', 'scope', 'document_type_code', 'title', 'reference_number',
                'issue_date', 'expiry_date', 'description',
            ],
            self::LeaveBalances => [
                'employee_code', 'leave_type_code', 'as_of_date', 'opening_units', 'source_reference',
            ],
            self::Financings => [
                'employee_code', 'type', 'request_date', 'principal', 'finance_charge',
                'installment_count', 'first_due_date', 'approved_source_reference',
            ],
            self::AssetCustody => [
                'employee_code', 'asset_number', 'issued_on', 'issued_condition',
                'issued_location', 'source_reference',
            ],
            self::HistoricalAttendance => [
                'employee_code', 'period_start', 'period_end', 'scheduled_days', 'present_days',
                'absent_days', 'half_days', 'leave_days', 'late_minutes', 'overtime_minutes',
                'unpaid_leave_units', 'source_reference',
            ],
        };
    }
}
