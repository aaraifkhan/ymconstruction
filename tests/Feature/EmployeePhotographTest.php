<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeePhotographTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_photograph_path_is_persisted_and_retrieved(): void
    {
        $employee = Employee::factory()->create(['photograph_path' => null]);
        $this->assertNull($employee->photograph_path);

        $employee->update(['photograph_path' => 'employees/photos/test.jpg']);

        $this->assertSame('employees/photos/test.jpg', $employee->fresh()->photograph_path);
    }

    public function test_photograph_path_raw_value_matches_plain_text(): void
    {
        $employee = Employee::factory()->create(['photograph_path' => 'employees/photos/portrait.png']);

        $raw = DB::table((new Employee)->getTable())
            ->where('id', $employee->getKey())
            ->value('photograph_path');

        $this->assertSame('employees/photos/portrait.png', $raw);
    }

    public function test_photograph_path_can_be_cleared(): void
    {
        $employee = Employee::factory()->create(['photograph_path' => 'employees/photos/old.jpg']);
        $employee->update(['photograph_path' => null]);

        $this->assertNull($employee->fresh()->photograph_path);
    }

    public function test_photograph_path_is_included_in_fillable(): void
    {
        $employee = Employee::factory()->make();

        $this->assertContains('photograph_path', $employee->getFillable());
    }
}
