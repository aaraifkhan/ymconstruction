<?php

namespace App\Filament\Resources\AssetCategories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asset category')->schema([
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('costAccount.code')->label('Cost account'),
                TextEntry::make('accumulatedDepreciationAccount.code')->label('Accumulated depreciation'),
                TextEntry::make('depreciationExpenseAccount.code')->label('Depreciation expense'),
                TextEntry::make('default_useful_life_months')->label('Life (months)'),
                IconEntry::make('is_depreciable')->boolean(),
                IconEntry::make('is_active')->boolean(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
