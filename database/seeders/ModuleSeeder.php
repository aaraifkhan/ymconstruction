<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'key' => 'documents',
                'name' => 'Document Management',
                'description' => 'Private, versioned documents linked to companies and business records.',
                'sort_order' => 10,
            ],
            [
                'key' => 'hr',
                'name' => 'Human Resources',
                'description' => 'Employees, employments, onboarding, compensation, and payroll.',
                'sort_order' => 20,
            ],
            [
                'key' => 'accounts',
                'name' => 'Accounts',
                'description' => 'Company accounting, Chart of Accounts, vouchers, banking, and financial reporting.',
                'sort_order' => 30,
            ],
            [
                'key' => 'projects',
                'name' => 'Projects',
                'description' => 'Projects, sites, procurement, material operations, costing, and billing.',
                'sort_order' => 40,
            ],
        ];

        foreach ($modules as $module) {
            Module::query()->updateOrCreate(
                ['key' => $module['key']],
                [...$module, 'is_active' => true],
            );
        }
    }
}
