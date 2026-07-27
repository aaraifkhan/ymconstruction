<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\Item;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('item_category_id')
                    ->numeric(),
                TextEntry::make('unitOfMeasure.name')
                    ->label('Unit of measure'),
                TextEntry::make('defaultTaxCode.name')
                    ->label('Default tax code')
                    ->placeholder('-'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('track_inventory')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Item $record): bool => $record->trashed()),
            ]);
    }
}
