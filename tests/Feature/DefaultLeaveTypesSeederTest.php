<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\LeaveType;
use Database\Seeders\DefaultLeaveTypesSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DefaultLeaveTypesSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_default_leave_types_seeder_populates_statutory_leaves(): void
    {
        $company = Company::factory()->create();

        $this->seed(DefaultLeaveTypesSeeder::class);

        $codes = LeaveType::query()->where('company_id', $company->getKey())->pluck('code')->all();

        $this->assertContains('CL', $codes);
        $this->assertContains('SL', $codes);
        $this->assertContains('AL', $codes);
        $this->assertContains('ML', $codes);
        $this->assertContains('PL', $codes);
        $this->assertContains('CPL', $codes);
        $this->assertContains('LWP', $codes);
    }
}
