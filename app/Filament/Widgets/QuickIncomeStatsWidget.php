<?php

namespace App\Filament\Widgets;

use App\Actions\Accounting\CheckAccountAvailableBalanceAction;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalEntry;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuickIncomeStatsWidget extends StatsOverviewWidget
{
    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

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

        // Today's income / receipts
        $todayIncome = JournalEntry::withoutGlobalScopes()
            ->join('journal_lines', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->whereIn('journal_entries.company_id', $companyIds)
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->whereDate('journal_entries.transaction_date', today())
            ->where(function ($q): void {
                $q->where('accounts.code', 'LIKE', '4%')
                    ->orWhere('accounts.code', 'LIKE', '3%')
                    ->orWhere('accounts.code', 'LIKE', '2220%')
                    ->orWhere('journal_entries.voucher_type', VoucherType::Receipt->value);
            })
            ->sum('journal_lines.credit') ?? '0.0000';

        // Month-to-date income / receipts
        $monthIncome = JournalEntry::withoutGlobalScopes()
            ->join('journal_lines', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->whereIn('journal_entries.company_id', $companyIds)
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->whereBetween('journal_entries.transaction_date', [today()->startOfMonth(), today()->endOfMonth()])
            ->where(function ($q): void {
                $q->where('accounts.code', 'LIKE', '4%')
                    ->orWhere('accounts.code', 'LIKE', '3%')
                    ->orWhere('accounts.code', 'LIKE', '2220%')
                    ->orWhere('journal_entries.voucher_type', VoucherType::Receipt->value);
            })
            ->sum('journal_lines.credit') ?? '0.0000';

        return [
            Stat::make('Cash in Hand Available', 'PKR '.number_format((float) $cashBalance, 2))
                ->description('Real-time posted cash accounts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Bank Balance Available', 'PKR '.number_format((float) $bankBalance, 2))
                ->description('Active bank accounts ledger total')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),

            Stat::make("Today's Income & Receipts", 'PKR '.number_format((float) $todayIncome, 2))
                ->description('Total inflows recorded today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Month-to-Date Income', 'PKR '.number_format((float) $monthIncome, 2))
                ->description(today()->format('F Y').' total receipts')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
