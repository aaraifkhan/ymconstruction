<?php

namespace App\Filament\Pages;

use App\Actions\Accounting\CheckAccountAvailableBalanceAction;
use App\Actions\Accounting\RecordQuickExpenseAction;
use App\Actions\Accounting\SubmitJournalEntryAction;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Enums\FinancialPeriodStatus;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Enums\VoucherType;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\FinancialPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Party;
use App\Models\Project;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MasterAccountsHubPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Master Accounts Hub';

    protected static ?string $title = 'Master Accounts Hub & Universal Transaction Center';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.master-accounts-hub';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getTenant() !== null
            && $user !== null
            && ($user->hasRole('super_admin') || $user->can('View:MasterAccountsHub'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $tenantId = Filament::getTenant()?->getKey();
        $openPeriod = FinancialPeriod::withoutGlobalScopes()
            ->where('company_id', $tenantId)
            ->where('status', FinancialPeriodStatus::Open)
            ->first();

        $this->form->fill([
            'target_company_id' => $tenantId,
            'entry_type' => 'expense',
            'transaction_date' => today()->toDateString(),
            'financial_period_id' => $openPeriod?->getKey(),
            'payment_method' => ExpensePaymentMethod::Cash->value,
            'category' => ExpenseCategory::Miscellaneous->value,
            'voucher_type' => VoucherType::Journal->value,
            'currency_code' => 'PKR',
            'lines' => [
                ['account_id' => null, 'debit' => 0, 'credit' => 0, 'description' => ''],
                ['account_id' => null, 'debit' => 0, 'credit' => 0, 'description' => ''],
            ],
        ]);
    }

    public function form(Schema $form): Schema
    {
        $user = Filament::auth()->user();
        $accessibleCompanyIds = $user?->hasRole('super_admin')
            ? Company::withoutGlobalScopes()->where('is_active', true)->pluck('id')->all()
            : $user?->companies()->wherePivot('is_active', true)->pluck('companies.id')->all() ?? [];

        return $form
            ->statePath('data')
            ->components([
                Section::make('Universal Cross-Company Transaction Entry')
                    ->description('Record accounting entries directly into any authorized company ledger without switching workspaces.')
                    ->columns(4)
                    ->schema([
                        Select::make('target_company_id')
                            ->label('Target Company')
                            ->options(fn () => Company::withoutGlobalScopes()->whereIn('id', $accessibleCompanyIds)->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => Filament::getTenant()?->getKey())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ($state) {
                                    $openPeriod = FinancialPeriod::withoutGlobalScopes()
                                        ->where('company_id', $state)
                                        ->where('status', FinancialPeriodStatus::Open)
                                        ->first();
                                    $set('financial_period_id', $openPeriod?->getKey());
                                    $set('company_bank_account_id', null);
                                    $set('project_id', null);
                                    $set('party_id', null);
                                }
                            }),

                        Select::make('entry_type')
                            ->label('Entry Mode')
                            ->options([
                                'expense' => '⚡ Quick Expense / Payment',
                                'journal' => '📑 Multi-Line Double Entry Journal',
                            ])
                            ->default('expense')
                            ->required()
                            ->live(),

                        DatePicker::make('transaction_date')
                            ->label('Transaction Date')
                            ->required()
                            ->default(today()->toDateString())
                            ->live(),

                        Select::make('financial_period_id')
                            ->label('Financial Period')
                            ->options(function (Get $get) {
                                $companyId = $get('target_company_id') ?? Filament::getTenant()?->getKey();

                                return FinancialPeriod::withoutGlobalScopes()
                                    ->where('company_id', $companyId)
                                    ->where('status', FinancialPeriodStatus::Open)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        // --- Quick Expense Mode Fields ---
                        Select::make('category')
                            ->label('Expense Head / Category')
                            ->options(collect(ExpenseCategory::cases())->mapWithKeys(fn (ExpenseCategory $cat) => [$cat->value => $cat->getLabel()]))
                            ->searchable()
                            ->visible(fn (Get $get) => $get('entry_type') === 'expense')
                            ->required(fn (Get $get) => $get('entry_type') === 'expense')
                            ->live(),

                        TextInput::make('amount')
                            ->label('Amount (PKR)')
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('PKR')
                            ->visible(fn (Get $get) => $get('entry_type') === 'expense')
                            ->required(fn (Get $get) => $get('entry_type') === 'expense'),

                        Select::make('payment_method')
                            ->label('Paid Via / Funded By')
                            ->options(collect(ExpensePaymentMethod::cases())->mapWithKeys(fn (ExpensePaymentMethod $method) => [$method->value => $method->getLabel()]))
                            ->visible(fn (Get $get) => $get('entry_type') === 'expense')
                            ->required(fn (Get $get) => $get('entry_type') === 'expense')
                            ->live(),

                        Select::make('company_bank_account_id')
                            ->label('Target Company Bank Account')
                            ->options(function (Get $get) {
                                $companyId = $get('target_company_id') ?? Filament::getTenant()?->getKey();

                                return CompanyBankAccount::withoutGlobalScopes()
                                    ->where('company_id', $companyId)
                                    ->where('is_active', true)
                                    ->pluck('bank_name', 'id');
                            })
                            ->visible(fn (Get $get) => $get('entry_type') === 'expense' && $get('payment_method') === ExpensePaymentMethod::Bank->value)
                            ->required(fn (Get $get) => $get('entry_type') === 'expense' && $get('payment_method') === ExpensePaymentMethod::Bank->value)
                            ->searchable(),

                        Select::make('project_id')
                            ->label('Project / Site (Cost Allocation)')
                            ->options(function (Get $get) {
                                $companyId = $get('target_company_id') ?? Filament::getTenant()?->getKey();

                                return Project::withoutGlobalScopes()->where('company_id', $companyId)->pluck('name', 'id');
                            })
                            ->visible(fn (Get $get) => $get('entry_type') === 'expense')
                            ->searchable(),

                        Select::make('party_id')
                            ->label('Payee / Vendor / Party (Optional)')
                            ->options(function (Get $get) {
                                $companyId = $get('target_company_id') ?? Filament::getTenant()?->getKey();

                                return Party::withoutGlobalScopes()->where('company_id', $companyId)->where('is_active', true)->pluck('name', 'id');
                            })
                            ->visible(fn (Get $get) => $get('entry_type') === 'expense')
                            ->searchable(),

                        TextInput::make('reference')
                            ->label('Bill / Voucher Ref')
                            ->maxLength(100)
                            ->visible(fn (Get $get) => $get('entry_type') === 'expense'),

                        Textarea::make('description')
                            ->label('Particulars / Description')
                            ->placeholder('e.g. Office generator fuel, drawings prints, petty tea')
                            ->visible(fn (Get $get) => $get('entry_type') === 'expense')
                            ->required(fn (Get $get) => $get('entry_type') === 'expense')
                            ->columnSpanFull(),

                        // --- Multi-Line Journal Mode Fields ---
                        Select::make('voucher_type')
                            ->label('Voucher Type')
                            ->options(collect([VoucherType::Journal, VoucherType::Payment, VoucherType::Receipt, VoucherType::Contra, VoucherType::DebitNote, VoucherType::CreditNote])
                                ->mapWithKeys(fn (VoucherType $type) => [$type->value => str($type->value)->headline()]))
                            ->visible(fn (Get $get) => $get('entry_type') === 'journal')
                            ->required(fn (Get $get) => $get('entry_type') === 'journal'),

                        TextInput::make('journal_reference')
                            ->label('Reference')
                            ->maxLength(120)
                            ->visible(fn (Get $get) => $get('entry_type') === 'journal'),

                        Textarea::make('journal_description')
                            ->label('Voucher Narration / Header Description')
                            ->visible(fn (Get $get) => $get('entry_type') === 'journal')
                            ->required(fn (Get $get) => $get('entry_type') === 'journal')
                            ->columnSpan(2),

                        Repeater::make('lines')
                            ->label('Double-Entry Voucher Lines')
                            ->visible(fn (Get $get) => $get('entry_type') === 'journal')
                            ->minItems(2)
                            ->schema([
                                Select::make('account_id')
                                    ->label('Account Head')
                                    ->options(function (Get $get) {
                                        $companyId = $get('../../target_company_id') ?? Filament::getTenant()?->getKey();

                                        return Account::withoutGlobalScopes()
                                            ->where('company_id', $companyId)
                                            ->where('is_active', true)
                                            ->whereDoesntHave('children')
                                            ->get()
                                            ->mapWithKeys(fn (Account $acc) => [$acc->id => "{$acc->code} — {$acc->name}"]);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2)
                                    ->live()
                                    ->helperText(function ($state, Get $get): ?string {
                                        if (! $state) {
                                            return null;
                                        }
                                        $account = Account::withoutGlobalScopes()->find($state);
                                        if (! $account) {
                                            return null;
                                        }
                                        $companyId = $get('../../target_company_id') ?? Filament::getTenant()?->getKey();
                                        $company = Company::withoutGlobalScopes()->find($companyId);
                                        if (! $company) {
                                            return null;
                                        }
                                        $balance = app(CheckAccountAvailableBalanceAction::class)->getAccountBalance($company, $account);
                                        $formatted = number_format((float) $balance, 2);
                                        $isDebit = ($account->normal_balance ?? NormalBalance::Debit) === NormalBalance::Debit;
                                        $sign = $isDebit ? ((float) $balance >= 0 ? 'Dr' : 'Cr') : ((float) $balance >= 0 ? 'Cr' : 'Dr');

                                        return "Current Ledger Balance: PKR {$formatted} ({$sign})";
                                    }),

                                TextInput::make('debit')
                                    ->label('Debit (PKR)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('PKR')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        if ((float) $state > 0) {
                                            $set('credit', 0);
                                        }
                                    }),

                                TextInput::make('credit')
                                    ->label('Credit (PKR)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('PKR')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        if ((float) $state > 0) {
                                            $set('debit', 0);
                                        }
                                    }),

                                Select::make('project_id')
                                    ->label('Project')
                                    ->options(function (Get $get) {
                                        $companyId = $get('../../target_company_id') ?? Filament::getTenant()?->getKey();

                                        return Project::query()->where('company_id', $companyId)->pluck('name', 'id');
                                    })
                                    ->searchable(),

                                Select::make('party_id')
                                    ->label('Party / Vendor')
                                    ->options(function (Get $get) {
                                        $companyId = $get('../../target_company_id') ?? Filament::getTenant()?->getKey();

                                        return Party::query()->where('company_id', $companyId)->where('is_active', true)->pluck('name', 'id');
                                    })
                                    ->searchable(),

                                TextInput::make('description')
                                    ->label('Line Particulars')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),

                        Placeholder::make('balance_summary')
                            ->label('')
                            ->visible(fn (Get $get) => $get('entry_type') === 'journal')
                            ->columnSpanFull()
                            ->content(function (Get $get): HtmlString {
                                $lines = $get('lines') ?? [];
                                $debitTotal = '0.0000';
                                $creditTotal = '0.0000';

                                foreach ($lines as $line) {
                                    $debit = (string) ($line['debit'] ?? 0);
                                    $credit = (string) ($line['credit'] ?? 0);
                                    $debitTotal = bcadd($debitTotal, is_numeric($debit) && (float) $debit > 0 ? $debit : '0', 4);
                                    $creditTotal = bcadd($creditTotal, is_numeric($credit) && (float) $credit > 0 ? $credit : '0', 4);
                                }

                                $diff = bcsub($debitTotal, $creditTotal, 4);
                                $isBalanced = bccomp($debitTotal, '0.0000', 4) > 0 && bccomp($diff, '0.0000', 4) === 0;

                                $debitFormatted = number_format((float) $debitTotal, 2);
                                $creditFormatted = number_format((float) $creditTotal, 2);
                                $diffFormatted = number_format(abs((float) $diff), 2);

                                if ($isBalanced) {
                                    return new HtmlString("
                                        <div style=\"display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-radius:8px; background-color:#ecfdf5; border:1px solid #10b981; color:#065f46; font-size:14px; margin-top:8px;\">
                                            <div><strong>Total Debit:</strong> PKR {$debitFormatted}</div>
                                            <div><strong>Total Credit:</strong> PKR {$creditFormatted}</div>
                                            <div style=\"font-weight:bold; color:#047857;\">✓ Balanced (PKR {$debitFormatted})</div>
                                        </div>
                                    ");
                                }

                                $warningText = bccomp($debitTotal, '0.0000', 4) === 0 && bccomp($creditTotal, '0.0000', 4) === 0
                                    ? 'Enter debit and credit amounts'
                                    : "Difference: PKR {$diffFormatted} (Out of Balance)";

                                return new HtmlString("
                                    <div style=\"display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-radius:8px; background-color:#fff1f2; border:1px solid #f43f5e; color:#9f1239; font-size:14px; margin-top:8px;\">
                                        <div><strong>Total Debit:</strong> PKR {$debitFormatted}</div>
                                        <div><strong>Total Credit:</strong> PKR {$creditFormatted}</div>
                                        <div style=\"font-weight:bold; color:#e11d48;\">⚠ {$warningText}</div>
                                    </div>
                                ");
                            }),
                    ]),
            ]);
    }

    public function submit(): void
    {
        try {
            $validated = $this->form->getState();
            $user = Filament::auth()->user();
            $targetCompany = Company::withoutGlobalScopes()->findOrFail($validated['target_company_id']);

            // Check if user is authorized for target company
            if (! $user->hasRole('super_admin') && ! $user->companies()->where('companies.id', $targetCompany->getKey())->wherePivot('is_active', true)->exists()) {
                Notification::make()->title('Access Denied')->body("You do not have active access to {$targetCompany->name}.")->danger()->send();

                return;
            }
            if ($validated['entry_type'] === 'expense') {
                $category = ExpenseCategory::from($validated['category']);
                $paymentMethod = ExpensePaymentMethod::from($validated['payment_method']);

                $journal = app(RecordQuickExpenseAction::class)->handle(
                    company: $targetCompany,
                    actor: $user,
                    date: CarbonImmutable::parse($validated['transaction_date']),
                    category: $category,
                    paymentMethod: $paymentMethod,
                    amount: (string) $validated['amount'],
                    description: $validated['description'],
                    projectId: ! empty($validated['project_id']) ? (int) $validated['project_id'] : null,
                    partyId: ! empty($validated['party_id']) ? (int) $validated['party_id'] : null,
                    companyBankAccountId: ! empty($validated['company_bank_account_id']) ? (int) $validated['company_bank_account_id'] : null,
                    reference: $validated['reference'] ?? null,
                );

                Notification::make()
                    ->title("Expense Recorded in {$targetCompany->name}")
                    ->body("Voucher {$journal->voucher_number} ({$category->getLabel()} - PKR ".number_format((float) $validated['amount'], 2).") was posted directly to {$targetCompany->name}'s ledger.")
                    ->success()
                    ->send();
            } else {
                // Multi-Line Journal Entry Mode
                $lines = $validated['lines'] ?? [];
                if (count($lines) < 2) {
                    throw ValidationException::withMessages(['lines' => 'A voucher requires at least two lines.']);
                }

                $period = FinancialPeriod::withoutGlobalScopes()
                    ->whereKey($validated['financial_period_id'])
                    ->where('company_id', $targetCompany->getKey())
                    ->firstOrFail();

                $voucherType = VoucherType::from($validated['voucher_type']);

                $journal = DB::transaction(function () use ($targetCompany, $period, $voucherType, $validated, $user, $lines): JournalEntry {
                    $entry = JournalEntry::create([
                        'company_id' => $targetCompany->getKey(),
                        'financial_year_id' => $period->financial_year_id,
                        'financial_period_id' => $period->getKey(),
                        'voucher_type' => $voucherType,
                        'idempotency_key' => (string) Str::uuid(),
                        'status' => JournalStatus::Draft,
                        'transaction_date' => $validated['transaction_date'],
                        'reference' => $validated['journal_reference'] ?? null,
                        'description' => $validated['journal_description'],
                        'narration' => $validated['journal_description'],
                        'currency_code' => 'PKR',
                        'prepared_by_id' => $user->getKey(),
                    ]);

                    $lineNumber = 1;
                    foreach ($lines as $lineData) {
                        $debit = (string) ($lineData['debit'] ?? '0');
                        $credit = (string) ($lineData['credit'] ?? '0');
                        if (bccomp($debit, '0.0000', 4) === 0 && bccomp($credit, '0.0000', 4) === 0) {
                            continue;
                        }

                        $account = Account::withoutGlobalScopes()->whereKey($lineData['account_id'])->where('company_id', $targetCompany->getKey())->firstOrFail();

                        JournalLine::create([
                            'journal_entry_id' => $entry->getKey(),
                            'company_id' => $targetCompany->getKey(),
                            'line_number' => $lineNumber++,
                            'account_id' => $account->getKey(),
                            'account_code_snapshot' => $account->code,
                            'account_name_snapshot' => $account->name,
                            'debit' => $debit,
                            'credit' => $credit,
                            'project_id' => ! empty($lineData['project_id']) ? (int) $lineData['project_id'] : null,
                            'party_id' => ! empty($lineData['party_id']) ? (int) $lineData['party_id'] : null,
                            'description' => $lineData['description'] ?? $validated['journal_description'],
                        ]);
                    }

                    app(SubmitJournalEntryAction::class)->handle($entry, $user);

                    return $entry->fresh();
                });

                Notification::make()
                    ->title("Journal Voucher Created in {$targetCompany->name}")
                    ->body("Voucher {$journal->voucher_number} was successfully submitted in {$targetCompany->name}.")
                    ->success()
                    ->send();
            }

            // Reset form fields
            $this->form->fill([
                'target_company_id' => $targetCompany->getKey(),
                'entry_type' => $validated['entry_type'],
                'transaction_date' => $validated['transaction_date'],
                'financial_period_id' => $validated['financial_period_id'],
                'payment_method' => ExpensePaymentMethod::Cash->value,
                'category' => ExpenseCategory::Miscellaneous->value,
                'voucher_type' => VoucherType::Journal->value,
                'currency_code' => 'PKR',
                'lines' => [
                    ['account_id' => null, 'debit' => 0, 'credit' => 0, 'description' => ''],
                    ['account_id' => null, 'debit' => 0, 'credit' => 0, 'description' => ''],
                ],
            ]);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            Notification::make()->title('Transaction Failed')->body($firstError)->danger()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getCompanySummariesProperty(): array
    {
        $user = Filament::auth()->user();
        if ($user === null) {
            return [];
        }

        $companies = $user->hasRole('super_admin')
            ? Company::withoutGlobalScopes()->where('is_active', true)->orderBy('name')->get()
            : $user->companies()->wherePivot('is_active', true)->orderBy('name')->get();

        $balanceService = app(CheckAccountAvailableBalanceAction::class);
        $summaries = [];

        foreach ($companies as $comp) {
            // Cash in hand
            $cashAccounts = $comp->accounts()->whereIn('code', ['1111', '1112'])->where('is_active', true)->get();
            $cashTotal = '0.0000';
            foreach ($cashAccounts as $acc) {
                $cashTotal = bcadd($cashTotal, $balanceService->getAccountBalance($comp, $acc), 4);
            }

            // Bank Accounts total
            $bankAccounts = $comp->bankAccounts()->where('is_active', true)->get();
            $bankTotal = '0.0000';
            foreach ($bankAccounts as $bank) {
                $bankTotal = bcadd($bankTotal, $balanceService->getBankAccountBalance($comp, $bank), 4);
            }

            // Today's posted expense outflow
            $todayExpenses = DB::table('journal_lines')
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
                ->where('journal_entries.company_id', $comp->getKey())
                ->where('journal_entries.status', JournalStatus::Posted->value)
                ->whereDate('journal_entries.transaction_date', today())
                ->where(function ($q): void {
                    $q->where('accounts.code', 'LIKE', '5%')
                        ->orWhere('accounts.code', 'LIKE', '6%')
                        ->orWhere('accounts.code', 'LIKE', '7%');
                })
                ->sum('journal_lines.debit') ?? '0.0000';

            // Pending Draft & Submitted vouchers
            $pendingVouchers = JournalEntry::withoutGlobalScopes()
                ->where('company_id', $comp->getKey())
                ->whereIn('status', [JournalStatus::Draft, JournalStatus::Submitted])
                ->count();

            $summaries[] = [
                'id' => $comp->getKey(),
                'name' => $comp->name,
                'code' => $comp->code,
                'cash_balance' => (float) $cashTotal,
                'bank_balance' => (float) $bankTotal,
                'total_liquid' => (float) bcadd($cashTotal, $bankTotal, 4),
                'today_expenses' => (float) $todayExpenses,
                'pending_vouchers' => $pendingVouchers,
            ];
        }

        return $summaries;
    }

    /** @return Collection<int, JournalEntry> */
    public function getRecentCrossCompanyEntriesProperty(): Collection
    {
        $user = Filament::auth()->user();
        if ($user === null) {
            return collect();
        }

        $companyIds = $user->hasRole('super_admin')
            ? Company::withoutGlobalScopes()->where('is_active', true)->pluck('id')->all()
            : $user->companies()->wherePivot('is_active', true)->pluck('companies.id')->all();

        return JournalEntry::withoutGlobalScopes()
            ->whereIn('company_id', $companyIds)
            ->latest('id')
            ->take(15)
            ->with(['company', 'lines.account', 'lines.project', 'preparedBy'])
            ->get();
    }
}
