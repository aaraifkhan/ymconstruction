<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Permission\Models\Role;

class RoleStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = Filament::auth()?->user();

        return $user !== null && ($user->hasRole('super_admin') || $user->can('View:RoleStatsOverview') || $user->can('view_role_stats_overview') || RoleResource::canViewAny());
    }

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
