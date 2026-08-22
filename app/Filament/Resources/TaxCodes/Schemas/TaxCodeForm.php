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
            Section::make('Tax Code Parameters')
                ->description('No statutory rate is supplied by the system. Activate only accountant-approved rates.')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('code')
                        ->label('Tax Code')
                        ->required()
                        ->alphaDash()
                        ->maxLength(50),
                    TextInput::make('name')
                        ->label('Tax Head / Name')
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->label('Tax Category')
                        ->options(TaxCodeType::class)
                        ->required(),
                    TextInput::make('rate')
                        ->label('Tax Rate (%)')
                        ->required()
                        ->numeric()
                        ->suffix('%')
                        ->minValue(0)
                        ->maxValue(100),
                    Select::make('calculation_method')
                        ->label('Calculation Method')
                        ->options(TaxCalculationMethod::class)
                        ->default(TaxCalculationMethod::Exclusive->value)
                        ->required(),
                    DatePicker::make('effective_from')
                        ->label('Effective From Date')
                        ->required(),
                    DatePicker::make('effective_to')
                        ->label('Effective To Date')
                        ->afterOrEqual('effective_from'),
                    Toggle::make('is_recoverable')
                        ->label('Is Recoverable (Input Tax)')
                        ->default(false),
                    Toggle::make('is_active')
                        ->label('Approved & Active')
                        ->default(false),
                    Textarea::make('notes')
                        ->label('Notes / Statutory References')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
