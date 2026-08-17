<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DepartmentHeadStatsWidget;
use App\Filament\Widgets\PendingApprovalsQueueWidget;
use App\Filament\Widgets\TeamWiseProductivityWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DepartmentHeadDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Department Operations';

    protected static ?string $navigationLabel = 'Head Operations Dashboard';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.department-head-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            DepartmentHeadStatsWidget::class,
            PendingApprovalsQueueWidget::class,
            TeamWiseProductivityWidget::class,
        ];
    }
}
