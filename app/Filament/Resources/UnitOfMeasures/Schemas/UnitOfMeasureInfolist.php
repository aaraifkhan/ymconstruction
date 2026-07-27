<?php

namespace App\Filament\Resources\UnitOfMeasures\Schemas;

use App\Models\UnitOfMeasure;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UnitOfMeasureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('symbol'),
                TextEntry::make('decimal_places')
                    ->numeric(),
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
                    ->visible(fn (UnitOfMeasure $record): bool => $record->trashed()),
            ]);
    }
}
