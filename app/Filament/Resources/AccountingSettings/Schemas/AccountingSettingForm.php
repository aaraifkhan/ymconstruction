<?php

namespace App\Filament\Resources\AccountingSettings\Schemas;

use App\Enums\AccountingProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountingSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Company Accounting Settings & Parameters')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Select::make('profile')->label('Accounting Profile')->options(collect(AccountingProfile::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))->required(),
                        TextInput::make('base_currency_code')->label('Base Currency')->required()->length(3)->default('PKR'),
                        TextInput::make('timezone')->label('Timezone')->required()->default('Asia/Karachi'),
                        TextInput::make('fiscal_year_start_month')->label('Fiscal Year Start Month (1-12)')->numeric()->minValue(1)->maxValue(12)->required(),
                        TextInput::make('fiscal_year_start_day')->label('Fiscal Year Start Day')->numeric()->default(1)->disabled(),
                        TextInput::make('monetary_precision')->label('Monetary Precision')->numeric()->minValue(2)->maxValue(6)->required(),
                        TextInput::make('display_precision')->label('Display Precision')->numeric()->minValue(0)->maxValue(6)->required(),
                        Toggle::make('allow_negative_inventory')->label('Allow Negative Inventory'),
                    ]),
            ]);
    }
}
