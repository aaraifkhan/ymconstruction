<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Permission\Models\Role;

class UserRoleStatsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::query()->count())
                ->description('Active user accounts')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->url(UserResource::canViewAny() ? UserResource::getUrl() : null),
            Stat::make('Total Roles', Role::query()->count())
                ->description('Configured access roles')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success')
                ->url(RoleResource::canViewAny() ? RoleResource::getUrl() : null),
        ];
    }
}
