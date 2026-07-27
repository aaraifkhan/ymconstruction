<?php

namespace App\Filament\Resources\ApMatchingSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApMatchingSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Three-way matching tolerances')
                ->description('Defaults are zero. These tolerances never permit billing beyond handed-over accepted quantity.')
                ->columns(3)
                ->schema([
                    TextInput::make('quantity_tolerance_percentage')->numeric()->minValue(0)->maxValue(100)->suffix('%')->required(),
                    TextInput::make('rate_tolerance_percentage')->numeric()->minValue(0)->maxValue(100)->suffix('%')->required(),
                    TextInput::make('tax_tolerance_percentage')->numeric()->minValue(0)->maxValue(100)->suffix('%')->required(),
                    Toggle::make('is_active')->default(true),
                ]),
        ]);
    }
}
