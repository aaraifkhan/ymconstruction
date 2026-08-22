<?php

namespace App\Filament\Resources\AppraisalCycles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppraisalCycleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Appraisal Cycle Configuration')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Cycle Name (e.g. Annual Review 2026)')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                    DatePicker::make('starts_on')
                        ->label('Period Start Date')
                        ->required(),
                    DatePicker::make('ends_on')
                        ->label('Period End Date')
                        ->required()
                        ->afterOrEqual('starts_on'),
                    TextInput::make('score_min')
                        ->label('Minimum Score')
                        ->numeric()
                        ->default(1)
                        ->required(),
                    TextInput::make('score_max')
                        ->label('Maximum Score')
                        ->numeric()
                        ->default(5)
                        ->required(),
                ]),
        ]);
    }
}
