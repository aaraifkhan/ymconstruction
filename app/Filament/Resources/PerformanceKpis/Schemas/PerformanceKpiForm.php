<?php

namespace App\Filament\Resources\PerformanceKpis\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PerformanceKpiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Performance KPI Metric Details')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('code')
                        ->label('KPI Code')
                        ->required()
                        ->alphaDash()
                        ->maxLength(50),
                    TextInput::make('name')
                        ->label('KPI Title / Metric Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('measurement_unit')
                        ->label('Unit of Measure (e.g. %, Hours, Tasks)')
                        ->maxLength(100),
                    Toggle::make('is_active')
                        ->label('Is Active')
                        ->default(true)
                        ->required(),
                    Textarea::make('description')
                        ->label('KPI Description & Target Objective')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
