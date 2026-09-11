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
            ->columns(1)
            ->components([
                Section::make('Bank & Branch Information')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('branch_name')
                            ->label('Branch Name')
                            ->maxLength(255),
                        TextInput::make('branch_code')
                            ->label('Branch Code')
                            ->maxLength(50),
                        TextInput::make('swift_code')
                            ->label('SWIFT / BIC Code')
                            ->maxLength(50),
                    ]),
                Section::make('Account Credentials & Purpose')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('account_title')
                            ->label('Account Title')
                            ->required()
                            ->maxLength(255),
                        Select::make('account_type')
                            ->label('Account Type')
                            ->options(
                                collect(BankAccountType::cases())
                                    ->mapWithKeys(fn (BankAccountType $type): array => [
                                        $type->value => $type->label(),
                                    ])
                                    ->all()
                            )
                            ->default(BankAccountType::Current->value)
                            ->required(),
                        TextInput::make('currency_code')
                            ->label('Currency')
                            ->default('PKR')
                            ->required()
                            ->length(3),
                        TextInput::make('account_number')
                            ->label('Account Number')
                            ->maxLength(100)
                            ->visible(
                                fn (string $operation, ?CompanyBankAccount $record): bool => $operation === 'create'
                                    || ($record !== null && Gate::allows('viewSensitive', $record))
                            ),
                        TextInput::make('iban')
                            ->label('IBAN (International Bank Account Number)')
                            ->maxLength(100)
                            ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2])
                            ->visible(
                                fn (string $operation, ?CompanyBankAccount $record): bool => $operation === 'create'
                                    || ($record !== null && Gate::allows('viewSensitive', $record))
                            ),
                        Toggle::make('is_default_for_payroll')
                            ->label('Default Payroll Disbursement Account')
                            ->helperText('Only one account per company will remain marked as the payroll default.'),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notes / Signatory Details')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
