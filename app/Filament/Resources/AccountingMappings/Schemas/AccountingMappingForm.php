<?php

namespace App\Filament\Resources\AccountingMappings\Schemas;

use App\Enums\AccountingMappingKey;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountingMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Accounting Mapping Configuration')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Select::make('account_id')->label('GL Account')->relationship('account', 'name')->searchable()->preload()->required(),
                        Select::make('system_key')->label('System Mapping Key')->options(collect(AccountingMappingKey::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->headline()])),
                        Select::make('company_bank_account_id')->label('Company Bank Account')->relationship('bankAccount', 'bank_name')->searchable()->preload(),
                        Toggle::make('is_active')->label('Is Active')->default(true),
                    ]),
            ]);
    }
}
