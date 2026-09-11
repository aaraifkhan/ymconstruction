<?php

namespace App\Filament\Widgets;

use App\Actions\Accounting\CheckAccountAvailableBalanceAction;
use App\Enums\JournalStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalEntry;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuickExpenseStatsWidget extends StatsOverviewWidget
{
    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getListeners(): array
    {
        return [
            'petty-cash-float-updated' => '$refresh',
        ];
    }

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $user = Filament::auth()->user();
        if ($user === null) {
            return [];
        }

        $companies = $tenant instanceof Company
            ? collect([$tenant])
            : ($user->hasRole('super_admin')
                ? Company::withoutGlobalScopes()->where('is_active', true)->get()
                : $user->companies()->wherePivot('is_active', true)->get());

        if ($companies->isEmpty()) {
            return [];
        }

        $balanceAction = app(CheckAccountAvailableBalanceAction::class);
        $cashBalance = '0.0000';
        $bankBalance = '0.0000';
        $companyIds = $companies->pluck('id')->all();

        foreach ($companies as $comp) {
            // Cash in hand accounts
            $cashAccounts = Account::withoutGlobalScopes()
                ->where('company_id', $comp->getKey())
                ->where(function ($q): void {
                    $q->where('code', 'LIKE', '1111%')
                        ->orWhere('code', 'LIKE', '1112%')
                        ->orWhere('name', 'LIKE', '%Cash in Hand%')
                        ->orWhere('name', 'LIKE', '%Petty Cash%');
                })
                ->get();

            foreach ($cashAccounts as $acc) {
                $cashBalance = bcadd($cashBalance, (string) $balanceAction->getAccountBalance($comp, $acc), 4);
            }

            // Bank balance
            $bankAccounts = CompanyBankAccount::query()
                ->where('company_id', $comp->getKey())
                ->where('is_active', true)
                ->get();

            foreach ($bankAccounts as $bankAcc) {
                $bankBalance = bcadd($bankBalance, (string) $balanceAction->getBankAccountBalance($comp, $bankAcc), 4);
            }
        }

        // Today's expenses
        $todayExpenses = JournalEntry::withoutGlobalScopes()
            ->join('journal_lines', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->whereIn('journal_entries.company_id', $companyIds)
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->whereDate('journal_entries.transaction_date', today())
            ->where(function ($q): void {
                $q->where('accounts.code', 'LIKE', '5%')
                    ->orWhere('accounts.code', 'LIKE', '6%')
                    ->orWhere('accounts.code', 'LIKE', '7%');
            })
            ->sum('journal_lines.debit') ?? '0.0000';

        // Month-to-date expenses
        $monthExpenses = JournalEntry::withoutGlobalScopes()
            ->join('journal_lines', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->whereIn('journal_entries.company_id', $companyIds)
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->whereBetween('journal_entries.transaction_date', [today()->startOfMonth(), today()->endOfMonth()])
            ->where(function ($q): void {
                $q->where('accounts.code', 'LIKE', '5%')
                    ->orWhere('accounts.code', 'LIKE', '6%')
                    ->orWhere('accounts.code', 'LIKE', '7%');
            })
            ->sum('journal_lines.debit') ?? '0.0000';

        return [
            Stat::make('Cash in Hand Available', 'PKR '.number_format((float) $cashBalance, 2))
                ->description('Real-time posted cash accounts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Bank Balance Available', 'PKR '.number_format((float) $bankBalance, 2))
                ->description('Active bank accounts ledger total')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),

            Stat::make("Today's Expenses", 'PKR '.number_format((float) $todayExpenses, 2))
                ->description('Total recorded today')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            Stat::make('Month-to-Date Expenses', 'PKR '.number_format((float) $monthExpenses, 2))
                ->description(today()->format('F Y').' total expenses')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
