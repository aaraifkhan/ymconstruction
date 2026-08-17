<?php

namespace App\Filament\Pages;

use App\Reports\DirectorExpenseLedgerReport;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DirectorExpenseLedgerPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Director Expense Ledger';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.director-expense-ledger';

    public string $fromDate = '';

    public string $toDate = '';

    /** @var array<string, mixed> */
    public array $report = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:TreasuryReports') || $user->can('ViewAny:JournalEntry'));
    }

    public function mount(DirectorExpenseLedgerReport $reportService): void
    {
        abort_unless(static::canAccess(), 403);
        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function updatedFromDate(DirectorExpenseLedgerReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function updatedToDate(DirectorExpenseLedgerReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function setThisMonth(DirectorExpenseLedgerReport $reportService): void
    {
        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function setLastMonth(DirectorExpenseLedgerReport $reportService): void
    {
        $lastMonth = today()->subMonth();
        $this->fromDate = $lastMonth->startOfMonth()->toDateString();
        $this->toDate = $lastMonth->endOfMonth()->toDateString();
        $this->loadReport($reportService);
    }

    public function loadReport(DirectorExpenseLedgerReport $reportService): void
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
