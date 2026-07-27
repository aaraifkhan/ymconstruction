<?php

namespace App\Filament\Resources\CompanyBankAccounts\Schemas;

use App\Enums\BankAccountType;
use App\Models\CompanyBankAccount;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class CompanyBankAccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bank account')
                    ->schema([
                        TextEntry::make('bank_name')
                            ->label('Bank name'),
                        TextEntry::make('branch_name')
                            ->label('Branch')
                            ->placeholder('Not provided'),
                        TextEntry::make('branch_code')
                            ->label('Branch code')
                            ->placeholder('Not provided'),
                        TextEntry::make('swift_code')
                            ->label('SWIFT / BIC code')
                            ->placeholder('Not provided'),
                        TextEntry::make('account_title')
                            ->label('Account title'),
                        TextEntry::make('account_type')
                            ->label('Account type')
                            ->formatStateUsing(fn (BankAccountType $state): string => $state->label()),
                        TextEntry::make('account_number')
                            ->label('Account number')
                            ->formatStateUsing(
                                fn (?string $state, CompanyBankAccount $record): string => Gate::allows('viewSensitive', $record)
                                    ? ($state ?? 'Not provided')
                                    : ($record->maskedAccountNumber() ?? 'Not provided')
                            )
                            ->copyable(fn (CompanyBankAccount $record): bool => Gate::allows('viewSensitive', $record)),
                        TextEntry::make('iban')
                            ->label('IBAN')
                            ->formatStateUsing(
                                fn (?string $state, CompanyBankAccount $record): string => Gate::allows('viewSensitive', $record)
                                    ? ($state ?? 'Not provided')
                                    : ($record->maskedIban() ?? 'Not provided')
                            )
                            ->copyable(fn (CompanyBankAccount $record): bool => Gate::allows('viewSensitive', $record)),
                        TextEntry::make('currency_code')
                            ->label('Currency'),
                        IconEntry::make('is_default_for_payroll')
                            ->label('Default payroll account')
                            ->boolean(),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        TextEntry::make('notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('System information')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->placeholder('Not archived'),
                    ])
                    ->columns(3)
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
