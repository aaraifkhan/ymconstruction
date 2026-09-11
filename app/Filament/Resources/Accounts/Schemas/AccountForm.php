<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Enums\AccountType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('General Ledger Account Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        TextInput::make('code')->label('Account Code')->required()->maxLength(50),
                        TextInput::make('name')->label('Account Title')->required()->maxLength(255),
                        Select::make('parent_id')->label('Parent Account')->relationship('parent', 'name')->searchable()->preload(),
                        Select::make('account_type')->label('Account Classification')->options(collect(AccountType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))->required(),
                        Select::make('normal_balance')->label('Normal Balance')->options(['debit' => 'Debit', 'credit' => 'Credit'])->required(),
                        TextInput::make('reporting_group')->label('Financial Reporting Group')->required()->maxLength(100),
                        Toggle::make('is_control_account')->label('Is Control Account')->live(),
                        Toggle::make('allows_manual_posting')->label('Allows Manual Posting')->disabled(fn ($get): bool => (bool) $get('is_control_account')),
                        Toggle::make('is_active')->label('Is Active')->default(true),
                    ]),
            ]);
    }
}
