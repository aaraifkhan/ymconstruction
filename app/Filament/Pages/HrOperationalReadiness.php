<?php

namespace App\Filament\Pages;

use App\Reports\HrOperationalReadinessReport;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class HrOperationalReadiness extends Page
{
    protected string $view = 'filament.pages.hr-operational-readiness';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static \UnitEnum|string|null $navigationGroup = 'HR';

    protected static ?string $navigationLabel = 'HR Readiness';

    /** @var array<string, mixed> */
    public array $report = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        $company = Filament::getTenant();

        return $user !== null
            && $company !== null
            && $user->canAccessTenant($company)
            && ($user->hasRole('super_admin') || $user->can('View:HrOperationalReadiness'));
    }

    public function mount(HrOperationalReadinessReport $report): void
    {
        abort_unless(static::canAccess(), 403);
        $this->report = $report->forCompany(Filament::getTenant(), Filament::auth()->user());
    }
}
