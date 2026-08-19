<?php

namespace App\Filament\Pages;

use App\Actions\Accounting\CheckAccountAvailableBalanceAction;
use App\Actions\Accounting\RecordQuickExpenseAction;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Filament\Widgets\QuickExpenseStatsWidget;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalEntry;
use App\Models\Party;
use App\Models\Project;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuickExpenseEntryPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounts Management';

    protected static ?string $navigationLabel = 'Quick Expense Entry';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.quick-expense-entry';

    public ?array $data = [];

    protected function getHeaderWidgets(): array
    {
        return [
            QuickExpenseStatsWidget::class,
        ];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('Create:JournalEntry'));
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill([
            'transaction_date' => today()->toDateString(),
            'payment_method' => ExpensePaymentMethod::Cash->value,
            'category' => ExpenseCategory::Miscellaneous->value,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $company = Filament::getTenant();

        return $form
            ->statePath('data')
            ->components([
                Section::make('Record Operational Expense')
                    ->description('Quickly enter expenses without writing manual double-entry lines. The system will automatically construct and post the balanced journal entry.')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('transaction_date')
                            ->label('Expense Date')
                            ->required()
                            ->default(today()->toDateString()),

                        Select::make('category')
                            ->label('Expense Head / Category')
                            ->options(collect(ExpenseCategory::cases())->mapWithKeys(fn (ExpenseCategory $cat) => [$cat->value => $cat->getLabel()]))
                            ->searchable()
                            ->required()
                            ->live(),

                        TextInput::make('amount')
                            ->label('Amount (PKR)')
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('PKR')
                            ->required(),

                        Select::make('payment_method')
                            ->label('Paid Via / Funded By')
                            ->options(collect(ExpensePaymentMethod::cases())->mapWithKeys(fn (ExpensePaymentMethod $method) => [$method->value => $method->getLabel()]))
                            ->required()
                            ->live(),

                        Select::make('company_bank_account_id')
                            ->label('Company Bank Account')
                            ->options(fn () => CompanyBankAccount::query()
                                ->where('company_id', $company?->getKey())
                                ->where('is_active', true)
                                ->pluck('bank_name', 'id'))
                            ->visible(fn ($get) => $get('payment_method') === ExpensePaymentMethod::Bank->value)
                            ->required(fn ($get) => $get('payment_method') === ExpensePaymentMethod::Bank->value)
                            ->searchable(),

                        Select::make('project_id')
                            ->label('Project / Site (Cost Allocation)')
                            ->options(fn () => Project::query()
                                ->where('company_id', Filament::getTenant()?->getKey())
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(fn ($get) => in_array($get('category'), array_map(fn ($c) => $c->value, array_filter(ExpenseCategory::cases(), fn ($c) => $c->isDirectProjectCost())), true)),

                        Select::make('party_id')
                            ->label('Payee / Vendor / Party (Optional)')
                            ->options(fn () => Party::query()
                                ->where('company_id', $company?->getKey())
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable(),

                        Select::make('expense_of_company_id')
                            ->label('Expense Of Company (For Shared Costs)')
                            ->options(fn () => Company::query()
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Use when an expense is paid from this company but belongs to another group company (e.g. 7-Orbit).'),

                        TextInput::make('reference')
                            ->label('Bill / Receipt Ref (Optional)')
                            ->maxLength(100),

                        Textarea::make('description')
                            ->label('Description / Particulars')
                            ->placeholder('e.g. Site generator fuel, Milk carton & tea for office, Drawing copies for C-21')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function submit(RecordQuickExpenseAction $action): void
    {
        $validated = $this->form->getState();
        $company = Filament::getTenant();
        $user = Filament::auth()->user();

        $category = ExpenseCategory::from($validated['category']);
        $paymentMethod = ExpensePaymentMethod::from($validated['payment_method']);

        try {
            $journal = $action->handle(
                company: $company,
                actor: $user,
                date: CarbonImmutable::parse($validated['transaction_date']),
                category: $category,
                paymentMethod: $paymentMethod,
                amount: (string) $validated['amount'],
                description: $validated['description'],
                projectId: ! empty($validated['project_id']) ? (int) $validated['project_id'] : null,
                partyId: ! empty($validated['party_id']) ? (int) $validated['party_id'] : null,
                companyBankAccountId: ! empty($validated['company_bank_account_id']) ? (int) $validated['company_bank_account_id'] : null,
                expenseOfCompanyId: ! empty($validated['expense_of_company_id']) ? (int) $validated['expense_of_company_id'] : null,
                reference: $validated['reference'] ?? null,
            );

            Notification::make()
                ->title('Expense Recorded Successfully')
                ->body("Voucher {$journal->voucher_number} ({$category->getLabel()} - PKR ".number_format((float) $validated['amount'], 2).') was submitted.')
                ->success()
                ->send();

            $this->form->fill([
                'transaction_date' => $validated['transaction_date'],
                'payment_method' => $validated['payment_method'],
                'category' => ExpenseCategory::Miscellaneous->value,
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Record Expense')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        $company = Filament::getTenant();

        return $table
            ->query(
                JournalEntry::query()
                    ->where('company_id', $company?->getKey())
                    ->with(['lines.account', 'lines.project', 'preparedBy'])
                    ->latest('transaction_date')
                    ->latest('id')
            )
            ->heading('Recently Recorded Expenses')
            ->description('Real-time audit log of operational expenses posted into this workspace')
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('voucher_number')
                    ->label('Voucher #')
                    ->placeholder('Draft')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->copyable(),
                TextColumn::make('description')
                    ->label('Description / Particulars')
                    ->limit(45)
                    ->tooltip(fn (JournalEntry $record): string => (string) $record->description)
                    ->searchable(),
                TextColumn::make('expense_account')
                    ->label('Expense Head')
                    ->state(function (JournalEntry $record): string {
                        $debitLine = $record->lines->firstWhere('debit', '>', 0);

                        return $debitLine?->account?->name ?? $debitLine?->account_name_snapshot ?? '-';
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('funded_by')
                    ->label('Paid Via')
                    ->state(function (JournalEntry $record): string {
                        $creditLine = $record->lines->firstWhere('credit', '>', 0);

                        return $creditLine?->account?->name ?? $creditLine?->account_name_snapshot ?? '-';
                    })
                    ->badge()
                    ->color('info'),
                TextColumn::make('project_name')
                    ->label('Project')
                    ->state(function (JournalEntry $record): string {
                        $debitLine = $record->lines->firstWhere('debit', '>', 0);

                        return $debitLine?->project?->name ?? '-';
                    })
                    ->placeholder('-'),
                TextColumn::make('debit_total')
                    ->label('Amount (PKR)')
                    ->money('PKR')
                    ->alignment(Alignment::End)
                    ->weight(FontWeight::Bold)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (JournalStatus $state) => $state->color()),
                TextColumn::make('preparedBy.name')
                    ->label('Recorded By')
                    ->placeholder('System')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(JournalStatus::class),
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('lines.project', 'name'),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }

    /** @return array<string, mixed> */
    public function getFinancialSummaryProperty(): array
    {
        $company = Filament::getTenant();
        if ($company === null) {
            return [
                'cash_balance' => 0.0,
                'bank_balance' => 0.0,
                'today_expenses' => 0.0,
                'month_expenses' => 0.0,
            ];
        }

        $balanceAction = app(CheckAccountAvailableBalanceAction::class);

        // Cash in hand accounts
        $cashAccounts = Account::withoutGlobalScopes()
            ->where('company_id', $company->getKey())
            ->where(function ($q): void {
                $q->where('code', 'LIKE', '1111%')
                    ->orWhere('code', 'LIKE', '1112%')
                    ->orWhere('name', 'LIKE', '%Cash in Hand%')
                    ->orWhere('name', 'LIKE', '%Petty Cash%');
            })
            ->get();

        $cashBalance = '0.0000';
        foreach ($cashAccounts as $acc) {
            $cashBalance = bcadd($cashBalance, (string) $balanceAction->getAccountBalance($company, $acc), 4);
        }

        // Bank balance
        $bankAccounts = CompanyBankAccount::query()
            ->where('company_id', $company->getKey())
            ->where('is_active', true)
            ->get();

        $bankBalance = '0.0000';
        foreach ($bankAccounts as $bankAcc) {
            $bankBalance = bcadd($bankBalance, (string) $balanceAction->getBankAccountBalance($company, $bankAcc), 4);
        }

        // Today's expenses
        $todayExpenses = JournalEntry::withoutGlobalScopes()
            ->join('journal_lines', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $company->getKey())
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
            ->where('journal_entries.company_id', $company->getKey())
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->whereBetween('journal_entries.transaction_date', [today()->startOfMonth(), today()->endOfMonth()])
            ->where(function ($q): void {
                $q->where('accounts.code', 'LIKE', '5%')
                    ->orWhere('accounts.code', 'LIKE', '6%')
                    ->orWhere('accounts.code', 'LIKE', '7%');
            })
            ->sum('journal_lines.debit') ?? '0.0000';

        return [
            'cash_balance' => (float) $cashBalance,
            'bank_balance' => (float) $bankBalance,
            'today_expenses' => (float) $todayExpenses,
            'month_expenses' => (float) $monthExpenses,
        ];
    }
}
