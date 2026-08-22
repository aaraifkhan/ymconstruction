<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = Filament::auth()?->user();

        return $user !== null && ($user->hasRole('super_admin') || $user->can('View:UserStatsOverview') || $user->can('view_user_stats_overview') || UserResource::canViewAny());
    }

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
