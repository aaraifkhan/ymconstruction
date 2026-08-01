<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceMonthlySummary;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class AttendanceTrendChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Attendance Trend';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        if ($tenant === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $summaries = AttendanceMonthlySummary::query()
            ->whereBelongsTo($tenant)
            ->selectRaw('period_start, SUM(present_days) as present, SUM(absent_days) as absent, SUM(half_days) as half, SUM(leave_days) as leave_d')
            ->groupBy('period_start')
            ->orderBy('period_start')
            ->take(6)
            ->get();

        $labels = $summaries->map(fn ($s) => $s->period_start->format('M Y'))->all();

        return [
            'datasets' => [
                [
                    'label' => 'Present Days',
                    'data' => $summaries->pluck('present')->all(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => 'Absent Days',
                    'data' => $summaries->pluck('absent')->all(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
                [
                    'label' => 'Half Days',
                    'data' => $summaries->pluck('half')->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
