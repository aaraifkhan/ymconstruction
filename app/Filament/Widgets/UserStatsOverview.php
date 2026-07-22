<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    protected int|array|null $columns = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::query()->count())
                ->description('Active user accounts')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->url(UserResource::canViewAny() ? UserResource::getUrl() : null),
        ];
    }
}
