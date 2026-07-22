<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Permission\Models\Role;

class RoleStatsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected int|array|null $columns = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Roles', Role::query()->count())
                ->description('Configured access roles')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success')
                ->url(RoleResource::canViewAny() ? RoleResource::getUrl() : null),
        ];
    }
}
