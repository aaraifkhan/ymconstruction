<?php

namespace App\Filament\Resources\FixedAssets\Schemas;

use App\Models\FixedAsset;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FixedAssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Fixed asset')->schema([
                TextEntry::make('asset_number'),
                TextEntry::make('name'),
                TextEntry::make('category.name')->label('Category'),
                TextEntry::make('status')->badge(),
                TextEntry::make('serial_number')->placeholder('—'),
                TextEntry::make('location')->placeholder('—'),
                TextEntry::make('project.name')->placeholder('—'),
                TextEntry::make('acquisition_source')->badge(),
                TextEntry::make('acquired_on')->date(),
                TextEntry::make('available_for_use_on')->date(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Valuation')->schema([
                TextEntry::make('acquisition_cost')->money('PKR'),
                TextEntry::make('residual_value')->money('PKR'),
                TextEntry::make('accumulated_depreciation')->money('PKR'),
                TextEntry::make('carrying_amount')->state(fn (FixedAsset $record): string => $record->carryingAmount())->money('PKR'),
                TextEntry::make('useful_life_months')->label('Life (months)'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
