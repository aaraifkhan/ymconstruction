<?php

namespace App\Filament\Resources\TaxCodes\Schemas;

use App\Enums\TaxCalculationMethod;
use App\Enums\TaxCodeType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaxCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tax code')
                ->description('No statutory rate is supplied by the system. Activate only accountant-approved rates.')
                ->schema([
                    TextInput::make('code')->required()->alphaDash()->maxLength(50),
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('type')->options(TaxCodeType::class)->required(),
                    TextInput::make('rate')
                        ->label('Rate (%)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    Select::make('calculation_method')
                        ->options(TaxCalculationMethod::class)
                        ->default(TaxCalculationMethod::Exclusive->value)
                        ->required(),
                    DatePicker::make('effective_from')->required(),
                    DatePicker::make('effective_to')->afterOrEqual('effective_from'),
                    Toggle::make('is_recoverable')->label('Recoverable')->default(false),
                    Toggle::make('is_active')->label('Approved and active')->default(false),
                    Textarea::make('notes')->rows(3)->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
