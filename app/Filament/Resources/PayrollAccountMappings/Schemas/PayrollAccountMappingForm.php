<?php

namespace App\Filament\Resources\PayrollAccountMappings\Schemas;

use App\Enums\AccountType;
use App\Enums\PayrollAccountComponent;
use App\Models\Account;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollAccountMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Payroll Account Mapping')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Select::make('component')->label('Payroll Component')->options(PayrollAccountComponent::class)->required(),
                        Select::make('account_id')->label('GL Account')
                            ->options(fn (): array => Account::query()->whereBelongsTo(Filament::getTenant())
                                ->whereIn('account_type', [AccountType::Expense, AccountType::Liability])
                                ->where('is_active', true)->where('allows_manual_posting', true)
                                ->orderBy('code')->get()->mapWithKeys(fn (Account $account): array => [
                                    $account->getKey() => "{$account->code} — {$account->name}",
                                ])->all())->searchable()->required()->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                        Toggle::make('is_active')->label('Is Active')->default(true),
                    ]),
            ]);
    }
}
