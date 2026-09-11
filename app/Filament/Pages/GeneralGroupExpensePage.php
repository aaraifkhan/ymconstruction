<?php

namespace App\Filament\Pages;

use App\Actions\Accounting\RecordPettyCashTopUpAction;
use App\Actions\Accounting\RecordQuickExpenseAction;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Filament\Widgets\QuickExpenseStatsWidget;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalEntry;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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

class GeneralGroupExpensePage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounts Management';

    protected static ?string $navigationLabel = 'General & Group Expenses';

    protected static ?string $title = 'General & Group Expenses (Shared Overheads & Common Assets)';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.general-group-expense';

    /** @var array<string, mixed> */
    public ?array $data = [];

    protected function getHeaderWidgets(): array
    {
        return [
            QuickExpenseStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('topUpGeneralFloat')
                ->label('Fund / Top-Up General Balance')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    DatePicker::make('date')
                        ->label('Date')
                        ->default(today()->toDateString())
                        ->required(),
                    Select::make('source_type')
                        ->label('Source of Funds / Injection')
                        ->options([
                            'director' => 'Director Advance / Funded (2220 Director Loan)',
                            'bank' => 'Corporate Bank Account Transfer',
                            'head_office_cash' => 'Head Office Cash (1111)',
                        ])
                        ->default('director')
                        ->live()
                        ->required(),
                    Select::make('company_bank_account_id')
                        ->label('Corporate Bank Account')
                        ->options(fn () => CompanyBankAccount::query()->where('company_id', $this->getCorporateCompany()?->getKey())->pluck('bank_name', 'id'))
                        ->visible(fn (Get $get) => $get('source_type') === 'bank')
                        ->required(fn (Get $get) => $get('source_type') === 'bank')
                        ->searchable(),
                    TextInput::make('amount')
                        ->label('Amount (PKR)')
                        ->numeric()
                        ->minValue(0.01)
                        ->prefix('PKR')
                        ->required(),
                    TextInput::make('description')
                        ->label('Narration / Reason')
                        ->default('General holding / petty cash float injection')
                        ->required(),
                ])
                ->action(function (array $data, RecordPettyCashTopUpAction $topUpAction): void {
                    $corporateCompany = $this->getCorporateCompany();
                    if (! $corporateCompany) {
                        Notification::make()
                            ->title('Corporate Entity Not Found')
                            ->body('No active corporate company was found to post this transaction.')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $user = Filament::auth()->user();
                        $journal = $topUpAction->handle(
                            company: $corporateCompany,
                            actor: $user,
                            date: CarbonImmutable::parse($data['date']),
                            amount: (string) $data['amount'],
                            sourceType: $data['source_type'],
                            description: $data['description'],
                            companyBankAccountId: ! empty($data['company_bank_account_id']) ? (int) $data['company_bank_account_id'] : null,
                            postImmediately: true,
                        );

                        Notification::make()
                            ->title('General Float / Balance Top-Up Successful')
                            ->body("Voucher {$journal->voucher_number} for PKR ".number_format((float) $data['amount'], 2)." was posted into {$corporateCompany->name}. Cash / petty cash balance is updated.")
                            ->success()
                            ->send();

                        $this->dispatch('petty-cash-float-updated');
                    } catch (\Throwable $exception) {
                        Notification::make()
                            ->title('Top-Up Failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        if ($user === null) {
            return false;
        }

        $hasPermission = $user->hasRole('super_admin')
            || $user->can('Create:JournalEntry')
            || $user->can('View:MasterAccountsHub');

        if (! $hasPermission) {
            return false;
        }

        return Filament::getTenant() !== null || Filament::getCurrentPanel()?->getId() === 'accounts-hub';
    }

    public function getCorporateCompany(): ?Company
    {
        return Company::withoutGlobalScopes()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->where('name', 'LIKE', '%7%Orbit%')
                    ->orWhere('slug', 'LIKE', '%7-orbit%');
            })
            ->first()
            ?? Company::withoutGlobalScopes()->where('is_active', true)->first();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $corporateCompany = $this->getCorporateCompany();
        $targetCompanyId = $corporateCompany?->getKey();

        $this->form->fill([
            'target_company_id' => $targetCompanyId,
            'transaction_date' => today()->toDateString(),
            'payment_method' => ExpensePaymentMethod::Cash->value,
            'category' => ExpenseCategory::Entertainment->value,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $user = Filament::auth()->user();
        $corporateCompany = $this->getCorporateCompany();
        $accessibleCompanyIds = $user?->hasRole('super_admin')
            ? Company::withoutGlobalScopes()->where('is_active', true)->pluck('id')->all()
            : $user?->companies()->wherePivot('is_active', true)->pluck('companies.id')->all() ?? [];

        return $form
            ->columns(1)
            ->statePath('data')
            ->components([
                Section::make('Record Combined / General Group Expense')
                    ->description('Record staff tea/refreshments, office entertainment, common utilities, and shared assets for the group in the Corporate Holding book (7 Orbit) with full visibility.')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('target_company_id')
                            ->label('Corporate Master Book (Holding Entity)')
                            ->options(fn () => Company::withoutGlobalScopes()->whereIn('id', $accessibleCompanyIds)->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => $corporateCompany?->getKey())
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Fixed to Corporate Master / Holding Book (7 Orbit) for all general shared expenses & group assets.')
                            ->columnSpanFull(),

                        DatePicker::make('transaction_date')
                            ->label('Expense Date')
                            ->required()
                            ->default(today()->toDateString()),

                        Select::make('category')
                            ->label('General Expense Head / Shared Asset')
                            ->options(ExpenseCategory::generalGroupOptions())
                            ->searchable()
                            ->required()
                            ->default(ExpenseCategory::Entertainment->value)
                            ->live()
                            ->helperText('Common staff tea, office refreshments, shared utilities, or group fixed assets.'),

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
                            ->label('Corporate Bank Account')
                            ->options(function () use ($corporateCompany) {
                                $compKey = $corporateCompany?->getKey();

                                return CompanyBankAccount::query()
                                    ->where('company_id', $compKey)
                                    ->where('is_active', true)
                                    ->pluck('bank_name', 'id');
                            })
                            ->visible(fn (Get $get) => $get('payment_method') === ExpensePaymentMethod::Bank->value)
                            ->required(fn (Get $get) => $get('payment_method') === ExpensePaymentMethod::Bank->value)
                            ->searchable(),

                        TextInput::make('reference')
                            ->label('Bill / Receipt Ref (Optional)')
                            ->maxLength(100),

                        Textarea::make('description')
                            ->label('Description / Particulars')
                            ->placeholder('e.g. Staff daily tea & milk supplies for all departments, Office guest refreshments, Pantry kettle & microwave')
                            ->required()
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }

    public function submit(RecordQuickExpenseAction $action): void
    {
        $validated = $this->form->getState();
        $user = Filament::auth()->user();
        $targetCompanyId = $validated['target_company_id'] ?? $this->getCorporateCompany()?->getKey();
        $targetCompany = Company::withoutGlobalScopes()->findOrFail($targetCompanyId);

        // Authorization check
        if (! $user->hasRole('super_admin') && ! $user->companies()->where('companies.id', $targetCompany->getKey())->wherePivot('is_active', true)->exists()) {
            Notification::make()
                ->title('Access Denied')
                ->body("You do not have active access to {$targetCompany->name}.")
                ->danger()
                ->send();

            return;
        }

        $category = ExpenseCategory::from($validated['category']);
        $paymentMethod = ExpensePaymentMethod::from($validated['payment_method']);

        try {
            $journal = $action->handle(
                company: $targetCompany,
                actor: $user,
                date: CarbonImmutable::parse($validated['transaction_date']),
                category: $category,
                paymentMethod: $paymentMethod,
                amount: (string) $validated['amount'],
                description: "[General Overhead] {$validated['description']}",
                projectId: null,
                partyId: null,
                companyBankAccountId: ! empty($validated['company_bank_account_id']) ? (int) $validated['company_bank_account_id'] : null,
                expenseOfCompanyId: null,
                reference: $validated['reference'] ?? null,
            );

            Notification::make()
                ->title("General Expense Recorded in {$targetCompany->name}")
                ->body("Voucher {$journal->voucher_number} ({$category->getLabel()} - PKR ".number_format((float) $validated['amount'], 2).') was submitted.')
                ->success()
                ->send();

            $this->form->fill([
                'target_company_id' => $targetCompany->getKey(),
                'transaction_date' => $validated['transaction_date'],
                'payment_method' => $validated['payment_method'],
                'category' => ExpenseCategory::Entertainment->value,
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Record General Expense')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        $company = Filament::getTenant();
        $user = Filament::auth()->user();
        $accessibleCompanyIds = $user?->hasRole('super_admin')
            ? Company::withoutGlobalScopes()->where('is_active', true)->pluck('id')->all()
            : $user?->companies()->wherePivot('is_active', true)->pluck('companies.id')->all() ?? [];

        $generalCodes = ['5150', '6700', '5400', '5450', '5500', '5800', '5900', '6900', '1280', '1200', '1230', '1240', '5200', '6600'];

        $query = JournalEntry::withoutGlobalScopes()
            ->whereHas('lines', function ($q) use ($generalCodes): void {
                $q->where(function ($sub) use ($generalCodes): void {
                    $sub->whereIn('account_code_snapshot', $generalCodes)
                        ->orWhere('description', 'LIKE', '%[General Overhead]%')
                        ->orWhere('description', 'LIKE', '%Tea%')
                        ->orWhere('description', 'LIKE', '%Refreshment%');
                });
            })
            ->with(['company', 'lines.account', 'preparedBy'])
            ->latest('transaction_date')
            ->latest('id');

        if ($company) {
            $query->where('company_id', $company->getKey());
        } else {
            $query->whereIn('company_id', $accessibleCompanyIds);
        }

        return $table
            ->query($query)
            ->heading('Recent General & Group Expenses')
            ->description('Real-time ledger of staff tea, entertainment, common utilities, and shared assets')
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Entity Book')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => Filament::getTenant() === null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('voucher_number')
                    ->label('Voucher #')
                    ->placeholder('Draft')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->copyable(),
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
                TextColumn::make('description')
                    ->label('Particulars')
                    ->limit(45)
                    ->tooltip(fn (JournalEntry $record): string => (string) $record->description)
                    ->searchable(),
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
                SelectFilter::make('company_id')
                    ->label('Entity')
                    ->options(fn () => Company::withoutGlobalScopes()->whereIn('id', $accessibleCompanyIds)->where('is_active', true)->pluck('name', 'id'))
                    ->visible(fn () => Filament::getTenant() === null),
                SelectFilter::make('status')
                    ->options(JournalStatus::class),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }
}
