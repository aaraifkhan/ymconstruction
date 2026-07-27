<?php

namespace App\Filament\Pages;

use App\Reports\CashBookReport;
use App\Reports\TreasuryPositionReport;
use App\Reports\UnreconciledBankItemReport;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class TreasuryReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Treasury & Banking';

    protected string $view = 'filament.pages.treasury-reports';

    /** @var array<int, array<string, mixed>> */
    public array $positions = [];

    /** @var array<string, string> */
    public array $cashBook = [];

    /** @var array<int, array<string, mixed>> */
    public array $unreconciled = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:TreasuryReports'));
    }

    public function mount(
        TreasuryPositionReport $positionReport,
        CashBookReport $cashBookReport,
        UnreconciledBankItemReport $unreconciledReport,
    ): void {
        abort_unless(static::canAccess(), 403);
        $company = Filament::getTenant();
        $this->positions = $positionReport->forCompany($company, today())->map(fn (array $row): array => [
            'account' => $row['mapping']->account->code.' — '.$row['mapping']->account->name,
            'bank' => $row['mapping']->bankAccount?->bank_name,
            'balance' => $row['balance'],
        ])->all();
        $cashBook = $cashBookReport->forCompany($company, today()->startOfMonth(), today());
        $this->cashBook = [
            'opening_balance' => $cashBook['opening_balance'],
            'debit_total' => $cashBook['debit_total'],
            'credit_total' => $cashBook['credit_total'],
            'closing_balance' => $cashBook['closing_balance'],
        ];
        $this->unreconciled = $company->bankAccounts()->where('is_active', true)->get()
            ->map(function ($bank) use ($company, $unreconciledReport): array {
                $items = $unreconciledReport->forBank($company, $bank, today());

                return [
                    'bank' => $bank->bank_name,
                    'account' => $bank->maskedAccountNumber(),
                    'statement_count' => $items['statement_items']->count(),
                    'book_count' => $items['book_items']->count(),
                ];
            })->all();
    }
}
