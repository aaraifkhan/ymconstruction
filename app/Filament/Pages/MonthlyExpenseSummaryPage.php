<?php

namespace App\Filament\Pages;

use App\Reports\MonthlyExpenseSummaryReport;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MonthlyExpenseSummaryPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Monthly Expense Summary';

    protected static ?int $navigationSort = 18;

    protected string $view = 'filament.pages.monthly-expense-summary';

    public string $fromDate = '';

    public string $toDate = '';

    /** @var array<string, mixed> */
    public array $report = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:TreasuryReports') || $user->can('View:AccountingReports'));
    }

    public function mount(MonthlyExpenseSummaryReport $reportService): void
    {
        abort_unless(static::canAccess(), 403);
        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function updatedFromDate(MonthlyExpenseSummaryReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function updatedToDate(MonthlyExpenseSummaryReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function setThisMonth(MonthlyExpenseSummaryReport $reportService): void
    {
        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function setLastMonth(MonthlyExpenseSummaryReport $reportService): void
    {
        $lastMonth = today()->subMonth();
        $this->fromDate = $lastMonth->startOfMonth()->toDateString();
        $this->toDate = $lastMonth->endOfMonth()->toDateString();
        $this->loadReport($reportService);
    }

    public function setThisFiscalYear(MonthlyExpenseSummaryReport $reportService): void
    {
        $now = today();
        $startYear = $now->month >= 7 ? $now->year : $now->year - 1;
        $start = CarbonImmutable::create($startYear, 7, 1);
        $this->fromDate = $start->toDateString();
        $this->toDate = $now->toDateString();
        $this->loadReport($reportService);
    }

    public function loadReport(MonthlyExpenseSummaryReport $reportService): void
    {
        $company = Filament::getTenant();
        if ($company === null) {
            $this->report = [];

            return;
        }

        $from = CarbonImmutable::parse($this->fromDate);
        $to = CarbonImmutable::parse($this->toDate);
        $this->report = $reportService->forCompany($company, $from, $to);
    }
}
