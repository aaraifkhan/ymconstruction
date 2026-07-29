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
            Section::make('Performance KPI')->schema([
                TextInput::make('code')->required()->alphaDash()->maxLength(50),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('measurement_unit')->maxLength(100),
                Toggle::make('is_active')->default(true)->required(),
                Textarea::make('description')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
