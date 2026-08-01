<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExpandedCompensationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_gross_salary_includes_all_nine_allowances(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();

        $compensation = EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'basic_salary' => '50000',
            'house_travel_allowance' => '5000',
            'fuel_allowance' => '3000',
            'mobile_allowance' => '1000',
            'internet_allowance' => '500',
            'food_allowance' => '2000',
            'site_allowance' => '1500',
            'project_allowance' => '2500',
            'other_allowance' => '1000',
        ]);

        // 50000 + 5000 + 3000 + 1000 + 500 + 2000 + 1500 + 2500 + 1000 = 66500
        $this->assertSame(66500.0, $compensation->grossSalary());
    }

    public function test_new_allowances_default_to_zero(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();

        $compensation = EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'basic_salary' => '100000',
            'house_travel_allowance' => '25000',
            'food_allowance' => '0',
            'other_allowance' => '0',
        ]);

        $this->assertSame(0.0, (float) ($compensation->fuel_allowance ?? 0));
        $this->assertSame(0.0, (float) ($compensation->mobile_allowance ?? 0));
        $this->assertSame(0.0, (float) ($compensation->internet_allowance ?? 0));
        $this->assertSame(0.0, (float) ($compensation->site_allowance ?? 0));
        $this->assertSame(0.0, (float) ($compensation->project_allowance ?? 0));
    }

    public function test_new_allowances_are_in_fillable(): void
    {
        $model = new EmploymentCompensation;
        $fillable = $model->getFillable();

        $this->assertContains('fuel_allowance', $fillable);
        $this->assertContains('mobile_allowance', $fillable);
        $this->assertContains('internet_allowance', $fillable);
        $this->assertContains('site_allowance', $fillable);
        $this->assertContains('project_allowance', $fillable);
    }
}
