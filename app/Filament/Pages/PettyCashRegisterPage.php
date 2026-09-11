<?php

namespace App\Filament\Pages;

use App\Actions\Accounting\PerformPettyCashReconciliationAction;
use App\Actions\Accounting\RecordPettyCashTopUpAction;
use App\Actions\Accounting\RecordQuickExpenseAction;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\PettyCashReconciliation;
use App\Models\Project;
use App\Reports\PettyCashRegisterReport;
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
use Illuminate\Database\Eloquent\Collection;

class PettyCashRegisterPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Petty Cash Register';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.petty-cash-register';

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

    public function mount(PettyCashRegisterReport $reportService): void
    {
        abort_unless(static::canAccess(), 403);
        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function updatedFromDate(PettyCashRegisterReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function updatedToDate(PettyCashRegisterReport $reportService): void
    {
        $this->loadReport($reportService);
    }

    public function setThisMonth(PettyCashRegisterReport $reportService): void
    {
        $this->fromDate = today()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
        $this->loadReport($reportService);
    }

    public function setLastMonth(PettyCashRegisterReport $reportService): void
    {
        $lastMonth = today()->subMonth();
        $this->fromDate = $lastMonth->startOfMonth()->toDateString();
        $this->toDate = $lastMonth->endOfMonth()->toDateString();
        $this->loadReport($reportService);
    }

    public function loadReport(PettyCashRegisterReport $reportService): void
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
            Action::make('addExpense')
                ->label('Record Petty Expense')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->form([
                    DatePicker::make('date')->label('Date')->default(today()->toDateString())->required(),
                    Select::make('category')
                        ->label('Expense Head')
                        ->options(collect(ExpenseCategory::cases())->mapWithKeys(fn (ExpenseCategory $c) => [$c->value => $c->getLabel()]))
                        ->searchable()
                        ->required(),
                    TextInput::make('amount')->label('Amount (PKR)')->numeric()->minValue(0.01)->prefix('PKR')->required(),
                    Select::make('project_id')
                        ->label('Project (Optional / Site Cost)')
                        ->options(fn () => Project::query()->where('company_id', Filament::getTenant()?->getKey())->pluck('name', 'id'))
                        ->searchable(),
                    Select::make('expense_of_company_id')
                        ->label('Expense Of Company (For Shared Tagging)')
                        ->options(fn () => Company::query()->where('is_active', true)->pluck('name', 'id'))
                        ->searchable(),
                    TextInput::make('description')->label('Particulars / Narration')->required(),
                ])
                ->action(function (array $data, RecordQuickExpenseAction $quickExpenseAction, PettyCashRegisterReport $reportService): void {
                    $company = Filament::getTenant();
                    $user = Filament::auth()->user();
                    $quickExpenseAction->handle(
                        company: $company,
                        actor: $user,
                        date: CarbonImmutable::parse($data['date']),
                        category: ExpenseCategory::from($data['category']),
                        paymentMethod: ExpensePaymentMethod::PettyCash,
                        amount: (string) $data['amount'],
                        description: $data['description'],
                        projectId: ! empty($data['project_id']) ? (int) $data['project_id'] : null,
                        expenseOfCompanyId: ! empty($data['expense_of_company_id']) ? (int) $data['expense_of_company_id'] : null,
                    );
                    Notification::make()->title('Petty cash expense recorded')->success()->send();
                    $this->loadReport($reportService);
                }),

            Action::make('topUp')
                ->label('Top-up Float')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    DatePicker::make('date')->label('Date')->default(today()->toDateString())->required(),
                    Select::make('source_type')
                        ->label('Source of Funds')
                        ->options([
                            'director' => 'Director Advance / Funded (Due to Director)',
                            'head_office_cash' => 'Head Office Cash (1111)',
                            'bank' => 'Company Bank Account Transfer',
                        ])
                        ->default('director')
                        ->live()
                        ->required(),
                    Select::make('company_bank_account_id')
                        ->label('Source Bank Account')
                        ->options(fn () => CompanyBankAccount::query()->where('company_id', Filament::getTenant()?->getKey())->pluck('bank_name', 'id'))
                        ->visible(fn ($get) => $get('source_type') === 'bank')
                        ->required(fn ($get) => $get('source_type') === 'bank'),
                    TextInput::make('amount')->label('Amount (PKR)')->numeric()->minValue(0.01)->prefix('PKR')->required(),
                    TextInput::make('description')->label('Narration / Memo')->default('Petty cash float replenishment')->required(),
                ])
                ->action(function (array $data, RecordPettyCashTopUpAction $topUpAction, PettyCashRegisterReport $reportService): void {
                    $company = Filament::getTenant();
                    $user = Filament::auth()->user();
                    $topUpAction->handle(
                        company: $company,
                        actor: $user,
                        date: CarbonImmutable::parse($data['date']),
                        amount: (string) $data['amount'],
                        sourceType: $data['source_type'],
                        description: $data['description'],
                        companyBankAccountId: ! empty($data['company_bank_account_id']) ? (int) $data['company_bank_account_id'] : null,
                    );
                    Notification::make()->title('Petty cash top-up recorded')->success()->send();
                    $this->loadReport($reportService);
                }),

            Action::make('reconcile')
                ->label('Physical Reconciliation')
                ->icon('heroicon-o-scale')
                ->color('warning')
                ->form([
                    DatePicker::make('date')->label('Reconciliation Date')->default(today()->toDateString())->required(),
                    TextInput::make('physical_cash')->label('Physical Cash Counted in Hand (PKR)')->numeric()->minValue(0)->prefix('PKR')->required(),
                    TextInput::make('on_account')->label('On-Account Cash Held by Staff (PKR)')->numeric()->minValue(0)->prefix('PKR')->default(0),
                    Textarea::make('explanation')->label('Notes / Variance Explanation'),
                ])
                ->action(function (array $data, PerformPettyCashReconciliationAction $reconciliationAction): void {
                    $company = Filament::getTenant();
                    $user = Filament::auth()->user();
                    $reconciliation = $reconciliationAction->handle(
                        company: $company,
                        actor: $user,
                        date: CarbonImmutable::parse($data['date']),
                        physicalCountedCash: (string) $data['physical_cash'],
                        onAccountHeld: (string) ($data['on_account'] ?? '0'),
                        explanation: $data['explanation'] ?? null,
                    );

                    $diff = (float) $reconciliation->difference;
                    if (abs($diff) < 0.01) {
                        Notification::make()->title('Reconciliation Matched Exactly (No Difference)')->success()->send();
                    } else {
                        Notification::make()
                            ->title('Reconciliation Saved with Difference')
                            ->body('System Difference: PKR '.number_format($diff, 2).' ('.($diff > 0 ? 'Shortage' : 'Surplus').')')
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }

    /** @return Collection<int, PettyCashReconciliation> */
    public function getRecentReconciliationsProperty()
    {
        $company = Filament::getTenant();
        if ($company === null) {
            return collect();
        }

        return PettyCashReconciliation::query()
            ->where('company_id', $company->getKey())
            ->latest('reconciliation_date')
            ->take(5)
            ->with('reconciledBy')
            ->get();
    }
}
