<?php

namespace App\Filament\Resources\DepreciationRuns\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepreciationRunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Depreciation Period & Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('financial_period_id')
                            ->label('Open Financial Period')
                            ->options(fn (): array => Filament::getTenant()?->financialPeriods()
                                ->where('status', 'open')->orderBy('starts_on')->get()
                                ->mapWithKeys(fn ($period): array => [$period->getKey() => "{$period->starts_on->format('d M Y')} – {$period->ends_on->format('d M Y')}"])
                                ->all() ?? [])
                            ->required(),
                        DatePicker::make('depreciation_date')
                            ->label('Depreciation Date')
                            ->required(),
                        TextInput::make('reference_number')
                            ->label('Run Reference Number')
                            ->disabled()
                            ->placeholder('Assigned when posted'),
                    ]),
            ]);
    }
}
