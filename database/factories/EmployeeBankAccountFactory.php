<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeBankAccount>
 */
class EmployeeBankAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'bank_name' => fake()->randomElement(['Meezan Bank', 'HBL', 'UBL', 'MCB']),
            'branch_name' => fake()->city().' Branch',
            'branch_code' => fake()->numerify('####'),
            'account_title' => fake()->name(),
            'account_number' => fake()->numerify('##############'),
            'iban' => 'PK'.fake()->numerify('######################'),
            'is_primary_for_payroll' => false,
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function payrollDefault(): static
    {
        return $this->state(fn (): array => ['is_primary_for_payroll' => true]);
    }
}
