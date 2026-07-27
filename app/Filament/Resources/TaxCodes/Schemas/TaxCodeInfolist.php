<?php

namespace App\Filament\Resources\TaxCodes\Schemas;

use App\Models\TaxCode;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaxCodeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('rate')
                    ->numeric(),
                TextEntry::make('calculation_method')
                    ->badge(),
                TextEntry::make('effective_from')
                    ->date(),
                TextEntry::make('effective_to')
                    ->date()
                    ->placeholder('-'),
                IconEntry::make('is_recoverable')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (TaxCode $record): bool => $record->trashed()),
            ]);
    }
}
