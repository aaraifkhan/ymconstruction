<?php

namespace App\Filament\Pages;

use App\Actions\Accounting\AllocateSharedOperatingExpenseAction;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class SharedCostAllocationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Accounts';

    protected static ?string $navigationLabel = 'Shared Cost Allocation';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.shared-cost-allocation';

    /** @var array<string, mixed> */
    public ?array $data = [];

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
        return $form
            ->statePath('data')
            ->schema([
                DatePicker::make('date')->label('Transaction Date')->default(today()->toDateString())->required(),
                Select::make('category')
                    ->label('Expense Head')
                    ->options(collect(ExpenseCategory::cases())->mapWithKeys(fn (ExpenseCategory $c) => [$c->value => $c->getLabel()]))
                    ->searchable()
                    ->required(),
                Select::make('payment_method')
                    ->label('Paid Via (Fund Source)')
                    ->options(collect(ExpensePaymentMethod::cases())->mapWithKeys(fn (ExpensePaymentMethod $m) => [$m->value => $m->getLabel()]))
                    ->live()
                    ->required(),
                Select::make('company_bank_account_id')
                    ->label('Company Bank Account')
                    ->options(fn () => CompanyBankAccount::query()->where('company_id', Filament::getTenant()?->getKey())->pluck('bank_name', 'id'))
                    ->visible(fn ($get) => $get('payment_method') === ExpensePaymentMethod::Bank->value)
                    ->required(fn ($get) => $get('payment_method') === ExpensePaymentMethod::Bank->value),
                TextInput::make('total_amount')->label('Total Bill / Invoice Amount (PKR)')->numeric()->minValue(0.01)->prefix('PKR')->required(),
                Textarea::make('description')->label('Memo / Narration')->required()->columnSpanFull(),

                Repeater::make('shares')
                    ->label('Inter-company Cost Share Breakdown')
                    ->schema([
                        Select::make('company_id')
                            ->label('Entity')
                            ->options(fn () => Company::query()->where('is_active', true)->pluck('name', 'id'))
                            ->disabled()
                            ->required(),
                        TextInput::make('amount')
                            ->label('Share Amount (PKR)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('PKR')
                            ->required(),
                    ])
                    ->columns(2)
                    ->addable(false)
                    ->deletable(false)
                    ->columnSpanFull(),
            ]);
    }

    public function submit(AllocateSharedOperatingExpenseAction $action): void
    {
        $state = $this->form->getState();
        $payingCompany = Filament::getTenant();
        $user = Filament::auth()->user();

        $shares = array_map(fn ($s) => [
            'company_id' => (int) $s['company_id'],
            'amount' => (string) $s['amount'],
        ], $state['shares']);

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
    }

    /** @return Collection<int, JournalEntry> */
    public function getRecentAllocationsProperty()
    {
        $company = Filament::getTenant();
        if ($company === null) {
            return collect();
        }

        return JournalEntry::query()
            ->where('company_id', $company->getKey())
            ->where('description', 'LIKE', 'Shared % allocation%')
            ->latest('transaction_date')
            ->take(10)
            ->with(['lines.account', 'lines.relatedCompany'])
            ->get();
    }
}
