<?php

namespace App\Filament\Pages;

use App\Models\FinancialYear;
use App\Reports\AccountingIntegrityReport;
use App\Reports\ConsolidatedFinancialReport;
use App\Reports\FinancialReportCsvExporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ConsolidatedReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Group Consolidation';

    protected string $view = 'filament.pages.consolidated-reports';

    /** @var array<int, array<string, mixed>> */
    public array $trialBalance = [];

    /** @var array<int, array<string, mixed>> */
    public array $reconciliation = [];

    /** @var array<int, string> */
    public array $companyNames = [];

    /** @var array<string, mixed> */
    public array $balanceSheet = [];

    /** @var array<string, mixed> */
    public array $profitAndLoss = [];

    public bool $reconciles = false;

    public bool $integrityPasses = false;

    public ?string $periodLabel = null;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:ConsolidatedReports'));
    }

    public function mount(ConsolidatedFinancialReport $report, AccountingIntegrityReport $integrity): void
    {
        abort_unless(static::canAccess(), 403);
        $root = Filament::getTenant();
        $year = FinancialYear::query()->where('company_id', $root->getKey())
            ->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())->first()
            ?? FinancialYear::query()->where('company_id', $root->getKey())->latest('starts_on')->first();
        if ($year === null) {
            return;
        }
        $asOf = min(today(), $year->ends_on);
        $result = $report->forGroup(Filament::auth()->user(), $root, $asOf);
        $this->periodLabel = "As of {$asOf->toDateString()}";
        $this->companyNames = $result['companies']->pluck('name')->all();
        $this->trialBalance = $result['trial_balance']->map(fn (array $row): array => [
            ...$row,
            'account_type' => $row['account_type']->value,
            'normal_balance' => $row['normal_balance']->value,
        ])->all();
        $this->reconciliation = $result['intercompany_reconciliation']->all();
        $this->balanceSheet = collect($result['balance_sheet'])->except(['assets', 'liabilities', 'equity'])->all();
        $this->profitAndLoss = collect($result['profit_and_loss'])->except(['revenue', 'expenses'])->all();
        $this->reconciles = $result['reconciles'];
        $this->integrityPasses = $result['companies']->every(fn ($company): bool => $integrity->forCompany($company)['passes']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('Export consolidated TB')->visible(fn (): bool => $this->trialBalance !== [])
                ->action(function (FinancialReportCsvExporter $exporter) {
                    $csv = $exporter->export(collect($this->trialBalance), [
                        'code' => 'Code', 'name' => 'Account', 'account_type' => 'Type',
                        'debit_balance' => 'Debit', 'credit_balance' => 'Credit',
                    ]);

                    return response()->streamDownload(fn () => print $csv, 'consolidated-trial-balance.csv', ['Content-Type' => 'text/csv']);
                }),
        ];
    }
}
