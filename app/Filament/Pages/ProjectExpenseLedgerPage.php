<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Reports\ProjectExpenseLedgerReport;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class ProjectExpenseLedgerPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static \UnitEnum|string|null $navigationGroup = 'Projects';

    protected static ?string $navigationLabel = 'Project Expense Ledger';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.project-expense-ledger';

    public ?int $selectedProjectId = null;

    public string $fromDate = '';

    public string $toDate = '';

    /** @var array<string, mixed> */
    public array $report = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:TreasuryReports') || $user->can('ViewAny:JournalEntry') || $user->can('ViewAny:Project'));
    }

    public function mount(ProjectExpenseLedgerReport $reportService): void
    {
        abort_unless(static::canAccess(), 403);
        $company = Filament::getTenant();
        $firstProject = Project::query()->where('company_id', $company?->getKey())->first();
        $this->selectedProjectId = $firstProject?->getKey();
        $this->loadReport($reportService);
    }

    public function updatedSelectedProjectId(ProjectExpenseLedgerReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function updatedFromDate(ProjectExpenseLedgerReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function updatedToDate(ProjectExpenseLedgerReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function clearDates(ProjectExpenseLedgerReport $reportService): void
    {
        $this->fromDate = '';
        $this->toDate = '';
        $this->loadReport($reportService);
    }

    public function loadReport(ProjectExpenseLedgerReport $reportService): void
    {
        $company = Filament::getTenant();
        if ($company === null || $this->selectedProjectId === null) {
            $this->report = [];

            return;
        }

        $project = Project::query()->where('company_id', $company->getKey())->find($this->selectedProjectId);
        if ($project === null) {
            $this->report = [];

            return;
        }

        $from = ! empty($this->fromDate) ? CarbonImmutable::parse($this->fromDate) : null;
        $to = ! empty($this->toDate) ? CarbonImmutable::parse($this->toDate) : null;

        $this->report = $reportService->forProject($company, $project, $from, $to);
    }

    /** @return Collection<int, Project> */
    public function getProjectsProperty()
    {
        $company = Filament::getTenant();
        if ($company === null) {
            return collect();
        }

        return Project::query()->where('company_id', $company->getKey())->orderBy('name')->get();
    }
}
