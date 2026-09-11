<?php

namespace App\Filament\Resources\BankStatements\Schemas;

use App\Models\CompanyBankAccount;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankStatementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Bank Statement Period & Balances')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('company_bank_account_id')
                            ->label('Company Bank Account')
                            ->options(fn (): array => CompanyBankAccount::query()
                                ->whereBelongsTo(Filament::getTenant())->where('is_active', true)
                                ->orderBy('bank_name')->get()->mapWithKeys(fn (CompanyBankAccount $bank): array => [
                                    $bank->getKey() => "{$bank->bank_name} — {$bank->maskedAccountNumber()}",
                                ])->all())
                            ->searchable()
                            ->required(),
                        DatePicker::make('period_start')
                            ->label('Period Start Date')
                            ->required(),
                        DatePicker::make('period_end')
                            ->label('Period End Date')
                            ->afterOrEqual('period_start')
                            ->required(),
                        TextInput::make('opening_balance')
                            ->label('Opening Statement Balance (PKR)')
                            ->numeric()
                            ->prefix('PKR')
                            ->required(),
                        TextInput::make('closing_balance')
                            ->label('Closing Statement Balance (PKR)')
                            ->numeric()
                            ->prefix('PKR')
                            ->required(),
                        TextInput::make('currency_code')
                            ->label('Currency')
                            ->default('PKR')
                            ->disabled()
                            ->dehydrated(),
                    ]),
            ]);
    }
}
