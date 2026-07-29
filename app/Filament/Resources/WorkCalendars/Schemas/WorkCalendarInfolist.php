<?php

namespace App\Filament\Resources\WorkCalendars\Schemas;

use App\Models\WorkCalendar;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WorkCalendarInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('timezone'),
                TextEntry::make('working_weekdays')
                    ->columnSpanFull(),
                TextEntry::make('effective_from')
                    ->date(),
                TextEntry::make('effective_to')
                    ->date()
                    ->placeholder('-'),
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
                    ->visible(fn (WorkCalendar $record): bool => $record->trashed()),
            ]);
    }
}
