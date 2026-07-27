<?php

namespace App\Filament\Resources\AccountingSettings\Schemas;

use App\Enums\AccountingProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AccountingSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('profile')->options(collect(AccountingProfile::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))->required(),
                TextInput::make('base_currency_code')->required()->length(3),
                TextInput::make('timezone')->required(),
                TextInput::make('fiscal_year_start_month')->numeric()->minValue(1)->maxValue(12)->required(),
                TextInput::make('fiscal_year_start_day')->numeric()->default(1)->disabled(),
                TextInput::make('monetary_precision')->numeric()->minValue(2)->maxValue(6)->required(),
                TextInput::make('display_precision')->numeric()->minValue(0)->maxValue(6)->required(),
                Toggle::make('allow_negative_inventory'),
            ]);
    }
}
