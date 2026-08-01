<?php

namespace Database\Seeders;

use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveUnit;
use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class DefaultLeaveTypesSeeder extends Seeder
{
    /**
     * Standard statutory and company leave types.
     *
     * @var array<int, array{code: string, name: string, unit: LeaveUnit, is_paid: bool, payroll_impact: LeavePayrollImpact, requires_attachment: bool}>
     */
    private const LEAVE_TYPES = [
        [
            'code' => 'CL',
            'name' => 'Casual Leave',
            'unit' => LeaveUnit::Day,
            'is_paid' => true,
            'payroll_impact' => LeavePayrollImpact::None,
            'requires_attachment' => false,
        ],
        [
            'code' => 'SL',
            'name' => 'Sick Leave',
            'unit' => LeaveUnit::Day,
            'is_paid' => true,
            'payroll_impact' => LeavePayrollImpact::None,
            'requires_attachment' => true,
        ],
        [
            'code' => 'AL',
            'name' => 'Annual Leave',
            'unit' => LeaveUnit::Day,
            'is_paid' => true,
            'payroll_impact' => LeavePayrollImpact::None,
            'requires_attachment' => false,
        ],
        [
            'code' => 'ML',
            'name' => 'Maternity Leave',
            'unit' => LeaveUnit::Day,
            'is_paid' => true,
            'payroll_impact' => LeavePayrollImpact::None,
            'requires_attachment' => true,
        ],
        [
            'code' => 'PL',
            'name' => 'Paternity Leave',
            'unit' => LeaveUnit::Day,
            'is_paid' => true,
            'payroll_impact' => LeavePayrollImpact::None,
            'requires_attachment' => false,
        ],
        [
            'code' => 'CPL',
            'name' => 'Compassionate Leave',
            'unit' => LeaveUnit::Day,
            'is_paid' => true,
            'payroll_impact' => LeavePayrollImpact::None,
            'requires_attachment' => false,
        ],
        [
            'code' => 'LWP',
            'name' => 'Leave Without Pay',
            'unit' => LeaveUnit::Day,
            'is_paid' => false,
            'payroll_impact' => LeavePayrollImpact::UnpaidDeduction,
            'requires_attachment' => false,
        ],
    ];

    public function run(): void
    {
        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach (self::LEAVE_TYPES as $type) {
                LeaveType::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'code' => $type['code'],
                    ],
                    [
                        'name' => $type['name'],
                        'unit' => $type['unit'],
                        'is_paid' => $type['is_paid'],
                        'payroll_impact' => $type['payroll_impact'],
                        'requires_attachment' => $type['requires_attachment'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
