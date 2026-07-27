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
        return $schema->components([
            Section::make('Depreciation period')->schema([
                Select::make('financial_period_id')
                    ->label('Open financial period')
                    ->options(fn (): array => Filament::getTenant()?->financialPeriods()
                        ->where('status', 'open')->orderBy('starts_on')->get()
                        ->mapWithKeys(fn ($period): array => [$period->getKey() => "{$period->starts_on->format('d M Y')} – {$period->ends_on->format('d M Y')}"])
                        ->all() ?? [])
                    ->required(),
                DatePicker::make('depreciation_date')->required(),
                TextInput::make('reference_number')->disabled()->placeholder('Assigned when posted'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
