<?php

namespace App\Filament\Resources\CompanyBankAccounts\Schemas;

use App\Enums\BankAccountType;
use App\Models\CompanyBankAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class CompanyBankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bank and branch')
                    ->schema([
                        TextInput::make('bank_name')
                            ->label('Bank name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('branch_name')
                            ->label('Branch name')
                            ->maxLength(255),
                        TextInput::make('branch_code')
                            ->label('Branch code')
                            ->maxLength(50),
                        TextInput::make('swift_code')
                            ->label('SWIFT / BIC code')
                            ->maxLength(50),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Account details')
                    ->schema([
                        TextInput::make('account_title')
                            ->label('Account title')
                            ->required()
                            ->maxLength(255),
                        Select::make('account_type')
                            ->label('Account type')
                            ->options(
                                collect(BankAccountType::cases())
                                    ->mapWithKeys(fn (BankAccountType $type): array => [
                                        $type->value => $type->label(),
                                    ])
                                    ->all()
                            )
                            ->default(BankAccountType::Current->value)
                            ->required(),
                        TextInput::make('account_number')
                            ->label('Account number')
                            ->maxLength(100)
                            ->visible(
                                fn (string $operation, ?CompanyBankAccount $record): bool => $operation === 'create'
                                    || ($record !== null && Gate::allows('viewSensitive', $record))
                            ),
                        TextInput::make('iban')
                            ->label('IBAN')
                            ->maxLength(100)
                            ->visible(
                                fn (string $operation, ?CompanyBankAccount $record): bool => $operation === 'create'
                                    || ($record !== null && Gate::allows('viewSensitive', $record))
                            ),
                        TextInput::make('currency_code')
                            ->label('Currency')
                            ->default('PKR')
                            ->required()
                            ->length(3),
                        Toggle::make('is_default_for_payroll')
                            ->label('Default payroll account')
                            ->helperText('Only one account per company will remain marked as the payroll default.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required(),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
