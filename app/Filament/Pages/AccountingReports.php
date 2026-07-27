<?php

namespace App\Filament\Pages;

use App\Models\FinancialYear;
use App\Reports\BalanceSheetReport;
use App\Reports\ProfitAndLossReport;
use App\Reports\TrialBalanceReport;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class AccountingReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Financial Statements';

    protected string $view = 'filament.pages.accounting-reports';

    /** @var array<int, array<string, mixed>> */
    public array $trialBalance = [];

    /** @var array<string, mixed> */
    public array $profitAndLoss = [];

    /** @var array<string, mixed> */
    public array $balanceSheet = [];

    public ?string $periodLabel = null;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:AccountingReports'));
    }

    public function mount(TrialBalanceReport $trial, ProfitAndLossReport $profitAndLoss, BalanceSheetReport $balanceSheet): void
    {
        abort_unless(static::canAccess(), 403);
        $company = Filament::getTenant();
        $year = FinancialYear::query()->where('company_id', $company->getKey())
            ->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())->first()
            ?? FinancialYear::query()->where('company_id', $company->getKey())->latest('starts_on')->first();

        if ($year === null) {
            return;
        }

        $asOf = min(today(), $year->ends_on);
        $this->periodLabel = "{$year->starts_on->toDateString()} to {$asOf->toDateString()}";
        $rows = $trial->forCompany($company, $asOf);
        $this->trialBalance = $rows->map(fn (array $row): array => [
            ...$row,
            'account_type' => $row['account_type']->value,
            'normal_balance' => $row['normal_balance']->value,
        ])->all();
        $this->profitAndLoss = $this->serializable($profitAndLoss->forCompany($company, $year->starts_on, $asOf));
        $this->balanceSheet = $this->serializable($balanceSheet->forCompany($company, $asOf));
    }

    /** @param array<string, mixed> $report */
    private function serializable(array $report): array
    {
        foreach ($report as $key => $value) {
            if ($value instanceof Collection) {
                $report[$key] = $value->map(fn (array $row): array => [
                    ...$row,
                    'account_type' => $row['account_type']->value,
                    'normal_balance' => $row['normal_balance']->value,
                ])->all();
            }
        }

        return $report;
    }
}
