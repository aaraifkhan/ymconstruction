<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Enums\AccountType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->required()->maxLength(50),
                TextInput::make('name')->required()->maxLength(255),
                Select::make('parent_id')->relationship('parent', 'name')->searchable()->preload(),
                Select::make('account_type')->options(collect(AccountType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))->required(),
                Select::make('normal_balance')->options(['debit' => 'Debit', 'credit' => 'Credit'])->required(),
                TextInput::make('reporting_group')->required()->maxLength(100),
                Toggle::make('is_control_account')->live(),
                Toggle::make('allows_manual_posting')->disabled(fn ($get): bool => (bool) $get('is_control_account')),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
