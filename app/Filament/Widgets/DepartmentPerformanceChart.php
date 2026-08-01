<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class DepartmentPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Department Headcount Breakdown';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        if ($tenant === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $departments = Department::query()
            ->whereBelongsTo($tenant)
            ->where('is_active', true)
            ->withCount(['employments' => fn ($query) => $query->whereIn('employment_status', ['active', 'probation'])])
            ->orderBy('name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Active Employees',
                    'data' => $departments->pluck('employments_count')->all(),
                    'backgroundColor' => ['#14bf97', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                ],
            ],
            'labels' => $departments->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
