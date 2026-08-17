<?php

namespace App\Filament\Pages;

use App\Reports\DailyCashBankPositionReport;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DailyCashBankPositionPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Daily Cash & Bank Statement';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.daily-cash-bank-position';

    public string $reportDate = '';

    /** @var array<string, mixed> */
    public array $report = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:TreasuryReports'));
    }

    public function mount(DailyCashBankPositionReport $reportService): void
    {
        abort_unless(static::canAccess(), 403);
        $this->reportDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function updatedReportDate(DailyCashBankPositionReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function previousDay(DailyCashBankPositionReport $reportService): void
    {
        $date = CarbonImmutable::parse($this->reportDate)->subDay();
        $this->reportDate = $date->toDateString();
        $this->loadReport($reportService);
    }

    public function nextDay(DailyCashBankPositionReport $reportService): void
    {
        $date = CarbonImmutable::parse($this->reportDate)->addDay();
        $this->reportDate = $date->toDateString();
        $this->loadReport($reportService);
    }

    public function setToday(DailyCashBankPositionReport $reportService): void
    {
        $this->reportDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function loadReport(DailyCashBankPositionReport $reportService): void
    {
        $company = Filament::getTenant();
        if ($company === null) {
            $this->report = [];

            return;
        }

        $date = CarbonImmutable::parse($this->reportDate);
        $this->report = $reportService->forCompany($company, $date);
    }
}
