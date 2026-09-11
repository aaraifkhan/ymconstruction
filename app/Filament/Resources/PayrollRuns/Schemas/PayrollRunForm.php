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
        return $schema
            ->columns(1)
            ->components([
                Section::make('Payroll Run Period & Parameters')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Payroll Batch / Ref #')
                            ->required()
                            ->maxLength(100),
                        DatePicker::make('period_start')
                            ->label('Pay Period Start')
                            ->required(),
                        DatePicker::make('period_end')
                            ->label('Pay Period End')
                            ->required()
                            ->afterOrEqual('period_start'),
                        TextInput::make('currency_code')
                            ->label('Currency')
                            ->default('PKR')
                            ->length(3)
                            ->required(),
                        Textarea::make('notes')
                            ->label('Private Processing Notes')
                            ->maxLength(5000)
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
