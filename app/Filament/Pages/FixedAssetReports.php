<?php

namespace App\Filament\Pages;

use App\Reports\FixedAssetReconciliationReport;
use App\Reports\FixedAssetRegisterReport;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class FixedAssetReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Fixed Assets';

    protected string $view = 'filament.pages.fixed-asset-reports';

    /** @var array<int, mixed> */
    public array $assets = [];

    /** @var array<int, array<string, mixed>> */
    public array $reconciliationRows = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:FixedAssetReports'));
    }

    public function mount(FixedAssetRegisterReport $register, FixedAssetReconciliationReport $reconciliation): void
    {
        abort_unless(static::canAccess(), 403);
        $company = Filament::getTenant();
        $this->assets = $register->forCompany($company)->all();
        $this->reconciliationRows = $reconciliation->forCompany($company)->all();
    }
}
