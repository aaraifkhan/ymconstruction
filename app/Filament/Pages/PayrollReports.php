<?php

namespace App\Filament\Pages;

use App\Reports\EmployeeAdvanceLedgerReport;
use App\Reports\PayrollReconciliationReport;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PayrollReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Payroll & Advances';

    protected string $view = 'filament.pages.payroll-reports';

    /** @var array<int, array<string, mixed>> */
    public array $reconciliationRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $advanceRows = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:PayrollReports'));
    }

    public function mount(PayrollReconciliationReport $reconciliation, EmployeeAdvanceLedgerReport $advances): void
    {
        abort_unless(static::canAccess(), 403);
        $company = Filament::getTenant();
        $this->reconciliationRows = $reconciliation->forCompany($company)->all();
        $this->advanceRows = $advances->forCompany($company)->map(fn ($line): array => [
            'employee' => $line->employment->employee->full_name,
            'date' => $line->journalEntry->transaction_date->toDateString(),
            'voucher' => $line->journalEntry->voucher_number,
            'description' => $line->description,
            'advance' => $line->debit,
            'recovery' => $line->credit,
        ])->all();
    }
}
