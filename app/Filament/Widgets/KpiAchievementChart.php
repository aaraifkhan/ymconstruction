<?php

namespace App\Filament\Widgets;

use App\Enums\EmploymentCategory;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class KpiAchievementChart extends ChartWidget
{
    protected ?string $heading = 'Employee Distribution by Category';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        if ($tenant === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $counts = Employment::query()
            ->whereBelongsTo($tenant)
            ->selectRaw('employment_category, COUNT(*) as aggregate')
            ->groupBy('employment_category')
            ->pluck('aggregate', 'employment_category');

        $labels = [];
        $data = [];

        foreach (EmploymentCategory::cases() as $category) {
            $labels[] = $category->label();
            $data[] = $counts->get($category->value, 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Staff Count',
                    'data' => $data,
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
