<?php

namespace App\Filament\Widgets;

use App\Enums\CompanyModuleState;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GlobalCompanyModulesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Filament::auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $companiesCount = Company::query()->where('is_active', true)->count();
        $modulesCount = Module::query()->where('is_active', true)->count();
        $totalMatrixCells = $companiesCount * $modulesCount;

        $enabledCount = CompanyModule::query()->withoutGlobalScopes()->where('state', CompanyModuleState::Enabled)->count();
        $disabledCount = CompanyModule::query()->withoutGlobalScopes()->where('state', CompanyModuleState::Disabled)->count();
        $inheritCount = CompanyModule::query()->withoutGlobalScopes()->where('state', CompanyModuleState::Inherit)->count();

        $coveragePercent = $totalMatrixCells > 0
            ? round((($enabledCount + $inheritCount) / $totalMatrixCells) * 100)
            : 100;

        return [
            Stat::make('Active Companies', (string) $companiesCount)
                ->description('Registered business tenants')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('System Modules', (string) $modulesCount)
                ->description('Granular capability packages')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Explicitly Enabled', (string) $enabledCount)
                ->description("{$coveragePercent}% effective system capability")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Explicitly Restricted', (string) $disabledCount)
                ->description("{$inheritCount} entries inheriting defaults")
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color($disabledCount > 0 ? 'danger' : 'gray'),
        ];
    }
}
