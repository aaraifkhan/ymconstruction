<?php

namespace App\Filament\Pages;

use App\Actions\Accounting\TransferBiddingCostToProjectAction;
use App\Models\Project;
use App\Reports\BiddingExpenseLedgerReport;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BiddingExpenseLedgerPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Bidding & Tender Expenses';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.bidding-expense-ledger';

    public string $fromDate = '';

    public string $toDate = '';

    /** @var array<string, mixed> */
    public array $report = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:TreasuryReports') || $user->can('ViewAny:JournalEntry'));
    }

    public function mount(BiddingExpenseLedgerReport $reportService): void
    {
        abort_unless(static::canAccess(), 403);
        $this->fromDate = today()->startOfYear()->toDateString();
        $this->toDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function updatedFromDate(BiddingExpenseLedgerReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function updatedToDate(BiddingExpenseLedgerReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function setThisMonth(BiddingExpenseLedgerReport $reportService): void
    {
        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function setLastMonth(BiddingExpenseLedgerReport $reportService): void
    {
        $lastMonth = today()->subMonth();
        $this->fromDate = $lastMonth->startOfMonth()->toDateString();
        $this->toDate = $lastMonth->endOfMonth()->toDateString();
        $this->loadReport($reportService);
    }

    public function loadReport(BiddingExpenseLedgerReport $reportService): void
    {
        $company = Filament::getTenant();
        if ($company === null) {
            $this->report = [];

            return;
        }

        $from = CarbonImmutable::parse($this->fromDate);
        $to = CarbonImmutable::parse($this->toDate);
        $this->report = $reportService->forCompany($company, $from, $to);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('transferToProject')
                ->label('Capitalize Bidding Cost to Won Project')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->form([
                    Select::make('project_id')
                        ->label('Awarded / Won Project')
                        ->options(fn () => Project::query()->where('company_id', Filament::getTenant()?->getKey())->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    DatePicker::make('date')->label('Transfer Date')->default(today()->toDateString())->required(),
                    TextInput::make('amount')->label('Amount to Capitalize (PKR)')->numeric()->minValue(0.01)->prefix('PKR')->required(),
                    Textarea::make('description')->label('Particulars')->default('Transfer of accumulated tender / bid costs into awarded project direct costs')->required(),
                ])
                ->action(function (array $data, TransferBiddingCostToProjectAction $action, BiddingExpenseLedgerReport $reportService): void {
                    $company = Filament::getTenant();
                    $user = Filament::auth()->user();
                    $project = Project::query()->where('company_id', $company->getKey())->findOrFail($data['project_id']);

                    $journal = $action->handle(
                        company: $company,
                        actor: $user,
                        project: $project,
                        date: CarbonImmutable::parse($data['date']),
                        amount: (string) $data['amount'],
                        description: $data['description'],
                    );

                    Notification::make()
                        ->title('Bidding Costs Transferred to Project')
                        ->body("Voucher {$journal->voucher_number} created transferring PKR ".number_format((float) $data['amount'], 2)." to Project {$project->name}.")
                        ->success()
                        ->send();

                    $this->loadReport($reportService);
                }),
        ];
    }
}
