<?php

namespace App\Filament\Resources\AccountingMappings\Schemas;

use App\Enums\AccountingMappingKey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AccountingMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_id')->relationship('account', 'name')->searchable()->preload()->required(),
                Select::make('system_key')->options(collect(AccountingMappingKey::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->headline()])),
                Select::make('company_bank_account_id')->relationship('bankAccount', 'bank_name')->searchable()->preload(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
