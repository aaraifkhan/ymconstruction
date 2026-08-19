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

class MasterAccountsOverviewWidget extends StatsOverviewWidget
{
    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        if ($user === null) {
            return [];
        }

        $companies = $user->hasRole('super_admin')
            ? Company::withoutGlobalScopes()->where('is_active', true)->orderBy('name')->get()
            : $user->companies()->wherePivot('is_active', true)->orderBy('name')->get();

        $balanceAction = app(CheckAccountAvailableBalanceAction::class);
        $stats = [];

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

            $cashTotal = '0.0000';
            foreach ($cashAccounts as $acc) {
                $cashTotal = bcadd($cashTotal, (string) $balanceAction->getAccountBalance($comp, $acc), 4);
            }

            // Bank balance
            $bankAccounts = CompanyBankAccount::query()
                ->where('company_id', $comp->getKey())
                ->where('is_active', true)
                ->get();

            $bankTotal = '0.0000';
            foreach ($bankAccounts as $bankAcc) {
                $bankTotal = bcadd($bankTotal, (string) $balanceAction->getBankAccountBalance($comp, $bankAcc), 4);
            }

            $totalLiquid = (float) bcadd($cashTotal, $bankTotal, 4);

            // Pending Draft & Submitted vouchers count
            $pendingVouchers = JournalEntry::withoutGlobalScopes()
                ->where('company_id', $comp->getKey())
                ->whereIn('status', [JournalStatus::Draft, JournalStatus::Submitted])
                ->count();

            $cashFormatted = number_format((float) $cashTotal, 2);
            $bankFormatted = number_format((float) $bankTotal, 2);

            $description = "Cash: PKR {$cashFormatted} | Bank: PKR {$bankFormatted}";
            if ($pendingVouchers > 0) {
                $description .= " • ⚠️ {$pendingVouchers} Pending";
            }

            $stats[] = Stat::make($comp->name.' ('.$comp->code.')', 'PKR '.number_format($totalLiquid, 2))
                ->description($description)
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color($pendingVouchers > 0 ? 'warning' : 'success');
        }

        return $stats;
    }
}
