<?php

namespace App\Filament\Resources\PayrollRuns\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollRunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payroll period')->schema([
                TextInput::make('reference_number')->required()->maxLength(100),
                TextInput::make('currency_code')->default('PKR')->length(3)->required(),
                DatePicker::make('period_start')->required(),
                DatePicker::make('period_end')->required()->afterOrEqual('period_start'),
                Textarea::make('notes')->label('Private notes')->maxLength(5000)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
