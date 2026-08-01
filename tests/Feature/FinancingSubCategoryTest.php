<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmployeeFinancing;
use App\Models\Employment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FinancingSubCategoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_financing_sub_category_persists_for_loan(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();

        $financing = EmployeeFinancing::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'sub_category' => 'vehicle_loan',
        ]);

        $this->assertSame('vehicle_loan', $financing->fresh()->sub_category);
    }

    public function test_financing_sub_category_persists_for_advance(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();

        $financing = EmployeeFinancing::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'sub_category' => 'medical_advance',
        ]);

        $this->assertSame('medical_advance', $financing->fresh()->sub_category);
    }

    public function test_financing_sub_category_is_nullable(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();

        $financing = EmployeeFinancing::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
            'sub_category' => null,
        ]);

        $this->assertNull($financing->fresh()->sub_category);
    }

    public function test_financing_sub_category_is_in_fillable(): void
    {
        $this->assertContains('sub_category', (new EmployeeFinancing)->getFillable());
    }
}
