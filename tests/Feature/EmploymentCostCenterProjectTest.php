<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Employment;
use App\Models\Project;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmploymentCostCenterProjectTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employment_can_have_cost_center_assigned(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create(['cost_center_id' => null]);
        $costCenter = CostCenter::factory()->create(['company_id' => $company->getKey()]);

        $employment->update(['cost_center_id' => $costCenter->getKey()]);

        $this->assertSame($costCenter->getKey(), $employment->fresh()->cost_center_id);
        $this->assertTrue($employment->fresh()->costCenter->is($costCenter));
    }

    public function test_employment_can_have_default_project_assigned(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create(['default_project_id' => null]);
        $project = Project::factory()->create(['company_id' => $company->getKey()]);

        $employment->update(['default_project_id' => $project->getKey()]);

        $this->assertSame($project->getKey(), $employment->fresh()->default_project_id);
        $this->assertTrue($employment->fresh()->defaultProject->is($project));
    }

    public function test_employment_cost_center_and_project_are_nullable(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create([
            'cost_center_id' => null,
            'default_project_id' => null,
        ]);

        $this->assertNull($employment->fresh()->cost_center_id);
        $this->assertNull($employment->fresh()->default_project_id);
    }

    public function test_employment_payment_method_defaults_to_bank_transfer(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();

        $this->assertSame('bank_transfer', $employment->fresh()->payment_method);
    }

    public function test_employment_payment_method_accepts_all_valid_values(): void
    {
        $company = Company::factory()->create();

        foreach (['bank_transfer', 'cash', 'cheque'] as $method) {
            $employment = Employment::factory()->forCompany($company)->create(['payment_method' => $method]);
            $this->assertSame($method, $employment->fresh()->payment_method);
        }
    }
}
