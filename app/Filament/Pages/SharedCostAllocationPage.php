<?php

namespace App\Filament\Pages;

use App\Actions\Accounting\AllocateSharedOperatingExpenseAction;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\JournalStatus;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalEntry;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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

class SharedCostAllocationPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounts Management';

    protected static ?string $navigationLabel = 'Shared Cost Allocation';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.shared-cost-allocation';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        if ($user === null) {
            return false;
        }

        $hasPermission = $user->hasRole('super_admin') || $user->can('Create:JournalEntry');
        if (! $hasPermission) {
            return false;
        }

        return Filament::getTenant() !== null || Filament::getCurrentPanel()?->getId() === 'accounts-hub';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $user = Filament::auth()->user();
        $payingCompanyId = Filament::getTenant()?->getKey();

        if (! $payingCompanyId) {
            $firstAccessible = $user?->hasRole('super_admin')
                ? Company::withoutGlobalScopes()->where('is_active', true)->first()
                : $user?->companies()->wherePivot('is_active', true)->first();
            $payingCompanyId = $firstAccessible?->getKey();
        }

        $this->form->fill([
            'paying_company_id' => $payingCompanyId,
            'date' => today()->toDateString(),
            'payment_method' => ExpensePaymentMethod::Cash->value,
            'description' => 'Shared head office monthly utility / expense split',
            'shares' => Company::query()->where('is_active', true)->get()->map(fn (Company $c) => [
                'company_id' => $c->getKey(),
                'company_name' => $c->name,
                'amount' => '0',
            ])->toArray(),
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
            ->schema([
                Section::make('Multi-Company Shared Expense Split')
                    ->description('Enter head office or joint expenses (e.g. utility bills, rent, internet) and allocate cost percentages/amounts across group entities.')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('paying_company_id')
                            ->label('Paying Entity (Fund Source Company)')
                            ->options(fn () => Company::withoutGlobalScopes()->whereIn('id', $accessibleCompanyIds)->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => Filament::getTenant()?->getKey() ?? ($accessibleCompanyIds[0] ?? null))
                            ->visible(fn () => Filament::getTenant() === null)
                            ->required(fn () => Filament::getTenant() === null)
                            ->live()
                            ->columnSpanFull(),

                        DatePicker::make('date')
                            ->label('Transaction Date')
                            ->default(today()->toDateString())
                            ->required(),

                        Select::make('category')
                            ->label('Expense Head / Category')
                            ->options(collect(ExpenseCategory::cases())->mapWithKeys(fn (ExpenseCategory $c) => [$c->value => $c->getLabel()]))
                            ->searchable()
                            ->required(),

                        TextInput::make('total_amount')
                            ->label('Total Invoice / Bill Amount (PKR)')
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('PKR')
                            ->required(),

                        Select::make('payment_method')
                            ->label('Paid Via (Fund Source)')
                            ->options(collect(ExpensePaymentMethod::cases())->mapWithKeys(fn (ExpensePaymentMethod $m) => [$m->value => $m->getLabel()]))
                            ->live()
                            ->required(),

                        Select::make('company_bank_account_id')
                            ->label('Company Bank Account')
                            ->options(function ($get) {
                                $payingKey = $get('paying_company_id') ?? Filament::getTenant()?->getKey();

                                return CompanyBankAccount::query()->where('company_id', $payingKey)->where('is_active', true)->pluck('bank_name', 'id');
                            })
                            ->visible(fn ($get) => $get('payment_method') === ExpensePaymentMethod::Bank->value)
                            ->required(fn ($get) => $get('payment_method') === ExpensePaymentMethod::Bank->value)
                            ->searchable(),

                        Textarea::make('description')
                            ->label('Memo / Narration')
                            ->placeholder('e.g. Head Office monthly electricity bill split for July')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),

                        Repeater::make('shares')
                            ->label('Inter-company Cost Share Breakdown')
                            ->schema([
                                Select::make('company_id')
                                    ->label('Entity')
                                    ->options(fn () => Company::query()->where('is_active', true)->pluck('name', 'id'))
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                TextInput::make('amount')
                                    ->label('Share Amount (PKR)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('PKR')
                                    ->required(),
                            ])
                            ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                            ->addable(false)
                            ->deletable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function submit(AllocateSharedOperatingExpenseAction $action): void
    {
        $state = $this->form->getState();
        $user = Filament::auth()->user();
        $payingCompanyId = $state['paying_company_id'] ?? Filament::getTenant()?->getKey();
        $payingCompany = Company::withoutGlobalScopes()->findOrFail($payingCompanyId);

        // Check if user is authorized for paying company
        if (! $user->hasRole('super_admin') && ! $user->companies()->where('companies.id', $payingCompany->getKey())->wherePivot('is_active', true)->exists()) {
            Notification::make()->title('Access Denied')->body("You do not have active access to {$payingCompany->name}.")->danger()->send();

            return;
        }

        $shares = array_map(fn ($s) => [
            'company_id' => (int) $s['company_id'],
            'amount' => (string) $s['amount'],
        ], $state['shares']);

        try {
            $res = $action->handle(
                payingCompany: $payingCompany,
                actor: $user,
                date: CarbonImmutable::parse($state['date']),
                category: ExpenseCategory::from($state['category']),
                paymentMethod: ExpensePaymentMethod::from($state['payment_method']),
                totalAmount: (string) $state['total_amount'],
                description: $state['description'],
                shares: $shares,
                companyBankAccountId: ! empty($state['company_bank_account_id']) ? (int) $state['company_bank_account_id'] : null,
            );

            Notification::make()
                ->title('Shared Cost Allocation Posted')
                ->body("Voucher {$res['paying_journal']->voucher_number} created with ".count($res['recipient_journals']).' reciprocal inter-company journal entries.')
                ->success()
                ->send();

            $this->mount();
        } catch (\Throwable $e) {
            Notification::make()->title('Allocation Failed')->body($e->getMessage())->danger()->send();
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
                $q->where('description', 'LIKE', 'Shared % allocation%')
                    ->orWhere('description', 'LIKE', '%split%')
                    ->orWhere('description', 'LIKE', '%shared%');
            })
            ->with(['company', 'lines.account', 'lines.relatedCompany', 'preparedBy'])
            ->latest('transaction_date')
            ->latest('id');

        if ($company) {
            $query->where('company_id', $company->getKey());
        } else {
            $query->whereIn('company_id', $accessibleCompanyIds);
        }

        return $table
            ->query($query)
            ->heading('Recent Shared Cost Allocation Entries')
            ->description('Inter-company expense allocations posted into ledger')
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Paying Company')
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
                TextColumn::make('intercompany_splits')
                    ->label('Entity Splits')
                    ->state(function (JournalEntry $record): string {
                        $debitLines = $record->lines->where('debit', '>', 0);
                        if ($debitLines->isEmpty()) {
                            return '-';
                        }

                        return $debitLines->map(function ($line) {
                            $name = $line->relatedCompany?->name ?? 'Own';

                            return "{$name}: PKR ".number_format((float) $line->debit, 2);
                        })->join(', ');
                    })
                    ->wrap(),
                TextColumn::make('debit_total')
                    ->label('Total Bill (PKR)')
                    ->money('PKR')
                    ->alignment(Alignment::End)
                    ->weight(FontWeight::Bold)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (JournalStatus $state) => $state->color()),
                TextColumn::make('preparedBy.name')
                    ->label('Prepared By')
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
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }
}
