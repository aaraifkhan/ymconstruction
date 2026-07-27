<?php

namespace App\Filament\Resources\DepreciationRuns\Schemas;

use App\Models\DepreciationRun;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepreciationRunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Depreciation run')->schema([
                TextEntry::make('reference_number')->placeholder('Pending'),
                TextEntry::make('status')->badge(),
                TextEntry::make('depreciation_date')->date(),
                TextEntry::make('financialPeriod.period_number')->label('Financial period'),
                TextEntry::make('asset_count')->state(fn (DepreciationRun $record): int => $record->lines()->count())->label('Assets'),
                TextEntry::make('total_amount')->money('PKR'),
                TextEntry::make('journalEntry.voucher_number')->label('Journal')->placeholder('—'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
