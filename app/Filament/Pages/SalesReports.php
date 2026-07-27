<?php

namespace App\Filament\Pages;

use App\Reports\AccountsReceivableAgingReport;
use App\Reports\ProjectBudgetVsActualReport;
use App\Reports\ProjectProfitabilityReport;
use App\Reports\UnpaidCustomerInvoiceReport;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class SalesReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Sales & Project Profitability';

    protected string $view = 'filament.pages.sales-reports';

    /** @var array<string, string> */
    public array $agingBuckets = [];

    /** @var array<int, array<string, mixed>> */
    public array $unpaidInvoices = [];

    /** @var array<int, array<string, mixed>> */
    public array $projects = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:SalesReports'));
    }

    public function mount(
        AccountsReceivableAgingReport $aging,
        UnpaidCustomerInvoiceReport $unpaid,
        ProjectProfitabilityReport $profitability,
        ProjectBudgetVsActualReport $budgetVsActual,
    ): void {
        abort_unless(static::canAccess(), 403);
        $company = Filament::getTenant();
        $this->agingBuckets = $aging->forCompany($company, today())['buckets'];
        $this->unpaidInvoices = $unpaid->forCompany($company)->map(fn (array $row): array => [
            'number' => $row['invoice']->invoice_number,
            'customer' => $row['invoice']->customer->name,
            'due_date' => $row['invoice']->due_date->toDateString(),
            'open_amount' => $row['open_amount'],
        ])->all();
        $this->projects = $company->projects()->orderBy('name')->get()->map(function ($project) use ($budgetVsActual, $company, $profitability): array {
            $profit = $profitability->forProject($company, $project, now()->startOfYear(), today());
            $budget = $budgetVsActual->forProject($company, $project, today());

            return [
                'code' => $project->code,
                'name' => $project->name,
                'revenue' => $profit['revenue'],
                'cost' => $profit['direct_cost'],
                'profit' => $profit['gross_profit'],
                'budget' => $budget['budget'],
                'variance' => $budget['variance'],
            ];
        })->all();
    }
}
