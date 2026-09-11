<?php

namespace App\Filament\Pages;

use App\Actions\Accounting\RecordQuickIncomeAction;
use App\Enums\ExpensePaymentMethod;
use App\Enums\IncomeCategory;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Filament\Widgets\QuickIncomeStatsWidget;
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

class QuickIncomeEntryPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownOnSquare;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounts Management';

    protected static ?string $navigationLabel = 'Quick Income Entry';

    protected static ?string $title = 'Quick Income & Fund Inflow Entry';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.quick-income-entry';

    /** @var array<string, mixed> */
    public ?array $data = [];

    protected function getHeaderWidgets(): array
    {
        return [
            QuickIncomeStatsWidget::class,
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

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $user = Filament::auth()->user();
        $targetCompanyId = Filament::getTenant()?->getKey();

        if (! $targetCompanyId) {
            $firstAccessible = $user?->hasRole('super_admin')
                ? Company::withoutGlobalScopes()->where('is_active', true)->first()
                : $user?->companies()->wherePivot('is_active', true)->first();
            $targetCompanyId = $firstAccessible?->getKey();
        }

        $this->form->fill([
            'target_company_id' => $targetCompanyId,
            'transaction_date' => today()->toDateString(),
            'receiving_method' => ExpensePaymentMethod::Bank->value,
            'income_category' => IncomeCategory::CustomerReceipt->value,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $user = Filament::auth()->user();
        $accessibleCompanyIds = $user?->hasRole('super_admin')
            ? Company::withoutGlobalScopes()->where('is_active', true)->pluck('id')->all()
            : $user?->companies()->wherePivot('is_active', true)->pluck('companies.id')->all() ?? [];

        return $form
            ->columns(1)
            ->statePath('data')
            ->components([
                Section::make('Record Income / Fund Receipt (Inflow)')
                    ->description('Quickly record income, customer receipts, capital injections, and fund inflows without writing manual double-entry lines. The system will automatically construct and post the balanced receipt voucher.')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('target_company_id')
                            ->label('Target Company')
                            ->options(fn () => Company::withoutGlobalScopes()->whereIn('id', $accessibleCompanyIds)->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => Filament::getTenant()?->getKey() ?? ($accessibleCompanyIds[0] ?? null))
                            ->visible(fn () => Filament::getTenant() === null)
                            ->required(fn () => Filament::getTenant() === null)
                            ->live()
                            ->columnSpanFull(),

                        DatePicker::make('transaction_date')
                            ->label('Receipt Date')
                            ->required()
                            ->default(today()->toDateString()),

                        Select::make('income_category')
                            ->label('Income Source / Head')
                            ->options(collect(IncomeCategory::cases())->mapWithKeys(fn (IncomeCategory $cat) => [$cat->value => $cat->getLabel()]))
                            ->searchable()
                            ->required()
                            ->default(IncomeCategory::CustomerReceipt->value)
                            ->live(),

                        TextInput::make('amount')
                            ->label('Amount (PKR)')
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('PKR')
                            ->required(),

                        Select::make('receiving_method')
                            ->label('Received Into')
                            ->options([
                                ExpensePaymentMethod::Bank->value => '🏦 Company Bank Account',
                                ExpensePaymentMethod::Cash->value => '💵 Cash in Hand (1111)',
                                ExpensePaymentMethod::PettyCash->value => '💼 Site Petty Cash Float (1112)',
                            ])
                            ->default(ExpensePaymentMethod::Bank->value)
                            ->required()
                            ->live(),

                        Select::make('company_bank_account_id')
                            ->label('Company Bank Account')
                            ->options(function ($get) {
                                $compKey = $get('target_company_id') ?? Filament::getTenant()?->getKey();

                                return CompanyBankAccount::query()
                                    ->where('company_id', $compKey)
                                    ->where('is_active', true)
                                    ->pluck('bank_name', 'id');
                            })
                            ->visible(fn ($get) => $get('receiving_method') === ExpensePaymentMethod::Bank->value)
                            ->required(fn ($get) => $get('receiving_method') === ExpensePaymentMethod::Bank->value)
                            ->searchable(),

                        Select::make('project_id')
                            ->label('Project / Site (Revenue Allocation)')
                            ->options(function ($get) {
                                $compKey = $get('target_company_id') ?? Filament::getTenant()?->getKey();

                                return Project::query()
                                    ->where('company_id', $compKey)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),

                        Select::make('party_id')
                            ->label('Customer / Client / Party (Optional)')
                            ->options(function ($get) {
                                $compKey = $get('target_company_id') ?? Filament::getTenant()?->getKey();

                                return Party::query()
                                    ->where('company_id', $compKey)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),

                        TextInput::make('reference')
                            ->label('Cheque / Receipt / Deposit Ref (Optional)')
                            ->maxLength(100),

                        Textarea::make('description')
                            ->label('Description / Particulars')
                            ->placeholder('e.g. Received customer milestone payment for C-21, Cash sales deposit, Director capital injection')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function submit(RecordQuickIncomeAction $action): void
    {
        $validated = $this->form->getState();
        $user = Filament::auth()->user();
        $targetCompanyId = $validated['target_company_id'] ?? Filament::getTenant()?->getKey();
        $company = Company::withoutGlobalScopes()->findOrFail($targetCompanyId);

        // Check if user is authorized for target company
        if (! $user->hasRole('super_admin') && ! $user->companies()->where('companies.id', $company->getKey())->wherePivot('is_active', true)->exists()) {
            Notification::make()->title('Access Denied')->body("You do not have active access to {$company->name}.")->danger()->send();

            return;
        }

        $category = IncomeCategory::from($validated['income_category']);
        $receivingMethod = ExpensePaymentMethod::from($validated['receiving_method']);

        try {
            $journal = $action->handle(
                company: $company,
                actor: $user,
                date: CarbonImmutable::parse($validated['transaction_date']),
                category: $category,
                receivingMethod: $receivingMethod,
                amount: (string) $validated['amount'],
                description: $validated['description'],
                projectId: ! empty($validated['project_id']) ? (int) $validated['project_id'] : null,
                partyId: ! empty($validated['party_id']) ? (int) $validated['party_id'] : null,
                companyBankAccountId: ! empty($validated['company_bank_account_id']) ? (int) $validated['company_bank_account_id'] : null,
                reference: $validated['reference'] ?? null,
            );

            Notification::make()
                ->title("Income / Receipt Recorded in {$company->name}")
                ->body("Receipt Voucher {$journal->voucher_number} ({$category->getLabel()} - PKR ".number_format((float) $validated['amount'], 2).') was posted.')
                ->success()
                ->send();

            $this->form->fill([
                'target_company_id' => $targetCompanyId,
                'transaction_date' => $validated['transaction_date'],
                'receiving_method' => $validated['receiving_method'],
                'income_category' => IncomeCategory::CustomerReceipt->value,
            ]);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to Record Income')
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

        $query = JournalEntry::withoutGlobalScopes()
            ->where(function ($q): void {
                $q->where('voucher_type', VoucherType::Receipt)
                    ->orWhereHas('lines.account', function ($sub): void {
                        $sub->where('code', 'LIKE', '4%')
                            ->orWhere('code', 'LIKE', '3%')
                            ->orWhere('code', 'LIKE', '2220%');
                    });
            })
            ->with(['company', 'lines.account', 'lines.project', 'preparedBy'])
            ->latest('transaction_date')
            ->latest('id');

        if ($company) {
            $query->where('company_id', $company->getKey());
        } else {
            $query->whereIn('company_id', $accessibleCompanyIds);
        }

        return $table
            ->query($query)
            ->heading('Recently Recorded Income & Receipts')
            ->description('Real-time audit log of income, client receipts, and capital inflows posted into ledger')
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Company')
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
                TextColumn::make('description')
                    ->label('Description / Particulars')
                    ->limit(45)
                    ->tooltip(fn (JournalEntry $record): string => (string) $record->description)
                    ->searchable(),
                TextColumn::make('income_source')
                    ->label('Income Head / Source')
                    ->state(function (JournalEntry $record): string {
                        $creditLine = $record->lines->firstWhere('credit', '>', 0);

                        return $creditLine?->account?->name ?? $creditLine?->account_name_snapshot ?? '-';
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('received_into')
                    ->label('Received Into')
                    ->state(function (JournalEntry $record): string {
                        $debitLine = $record->lines->firstWhere('debit', '>', 0);

                        return $debitLine?->account?->name ?? $debitLine?->account_name_snapshot ?? '-';
                    })
                    ->badge()
                    ->color('info'),
                TextColumn::make('project_name')
                    ->label('Project')
                    ->state(function (JournalEntry $record): string {
                        $lineWithProject = $record->lines->first(fn ($l) => $l->project_id !== null);

                        return $lineWithProject?->project?->name ?? '-';
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
                SelectFilter::make('company_id')
                    ->label('Company')
                    ->options(fn () => Company::withoutGlobalScopes()->whereIn('id', $accessibleCompanyIds)->where('is_active', true)->pluck('name', 'id'))
                    ->visible(fn () => Filament::getTenant() === null),
                SelectFilter::make('status')
                    ->options(JournalStatus::class),
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('lines.project', 'name'),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }
}
