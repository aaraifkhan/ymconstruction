<?php

namespace Tests\Feature;

use App\Actions\Payroll\GenerateSalarySlipAction;
use App\Models\Company;
use App\Models\Employment;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SalarySlipPdfTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_salary_slip_pdf_generation_produces_valid_pdf_content(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $run = PayrollRun::query()->create([
            'company_id' => $company->getKey(),
            'reference_number' => 'PR-2026-07',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'period_days' => 31,
            'status' => 'draft',
        ]);
        $entry = PayrollEntry::query()->create([
            'company_id' => $company->getKey(),
            'payroll_run_id' => $run->getKey(),
            'employment_id' => $employment->getKey(),
            'employee_code' => $employment->employee_code,
            'employee_name' => $employment->employee->full_name,
            'employment_category' => $employment->employment_category,
            'period_days' => 31,
            'payable_days' => 31,
            'basic_salary' => 50000,
            'payable_basic' => 50000,
            'house_travel_allowance' => 5000,
            'fuel_allowance' => 3000,
            'mobile_allowance' => 1000,
            'internet_allowance' => 500,
            'food_allowance' => 2000,
            'site_allowance' => 1500,
            'project_allowance' => 2500,
            'other_allowance' => 1000,
            'bonus_amount' => 0,
            'incentive_amount' => 0,
            'gross_salary' => 66500,
            'absence_deduction' => 0,
            'unpaid_leave_deduction' => 0,
            'late_deduction' => 0,
            'half_day_deduction' => 0,
            'loan_advance_deduction' => 0,
            'other_deduction' => 0,
            'net_salary' => 66500,
            'bank_amount' => 66500,
            'cash_amount' => 0,
            'currency_code' => 'PKR',
        ]);

        $action = new GenerateSalarySlipAction;
        $pdfOutput = $action->handle($entry);

        $this->assertNotEmpty($pdfOutput);
        $this->assertStringStartsWith('%PDF-', $pdfOutput);
    }
}
