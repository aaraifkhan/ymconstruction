<?php

namespace App\Filament\Pages;

use App\Reports\FinalSettlementReport;
use App\Reports\HrReportCsvExporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalSettlementReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Final Settlements';

    protected string $view = 'filament.pages.final-settlement-reports';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null && $user !== null
            && ($user->hasRole('super_admin') || (
                $user->can('View:FinalSettlementReport')
                && $user->can('ViewAmounts:FinalSettlement')
            ));
    }

    public function mount(FinalSettlementReport $report): void
    {
        abort_unless(static::canAccess(), 403);
        $this->rows = $report->forCompany(Filament::getTenant())->all();
    }

    public function export(HrReportCsvExporter $exporter, string $format = 'csv'): StreamedResponse
    {
        $user = Filament::auth()->user();
        abort_unless(static::canAccess() && ($user->hasRole('super_admin') || $user->can('Export:FinalSettlementReport')), 403);

        return $exporter->download($user, Filament::getTenant(), 'final-settlement', collect($this->rows), [
            'reference_number' => 'Settlement', 'employee_code' => 'Employee Code',
            'employee_name' => 'Employee', 'cutoff_date' => 'Cutoff Date', 'status' => 'Status',
            'earning_total' => 'Earnings', 'recovery_total' => 'Recoveries',
            'balance_direction' => 'Direction', 'net_amount' => 'Net', 'gl_voucher' => 'GL Voucher',
            'gl_amount' => 'GL Amount', 'treasury_settled' => 'Treasury Settled', 'open_amount' => 'Open Amount',
            'operational_gl_reconciled' => 'GL Reconciled', 'treasury_reconciled' => 'Treasury Reconciled',
        ], format: $format);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')->label('Export CSV')
                ->visible(fn (): bool => Filament::auth()->user()?->hasRole('super_admin')
                    || Filament::auth()->user()?->can('Export:FinalSettlementReport'))
                ->action(fn () => $this->export(app(HrReportCsvExporter::class))),
            Action::make('exportXlsx')->label('Export Excel')
                ->visible(fn (): bool => Filament::auth()->user()?->hasRole('super_admin')
                    || Filament::auth()->user()?->can('Export:FinalSettlementReport'))
                ->action(fn () => $this->export(app(HrReportCsvExporter::class), 'xlsx')),
        ];
    }
}
