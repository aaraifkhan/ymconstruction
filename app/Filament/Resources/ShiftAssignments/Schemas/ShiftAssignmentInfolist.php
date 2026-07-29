<?php

namespace App\Filament\Resources\ShiftAssignments\Schemas;

use App\Models\ShiftAssignment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ShiftAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('employment.id')
                    ->label('Employment'),
                TextEntry::make('workCalendar.name')
                    ->label('Work calendar'),
                TextEntry::make('workShift.name')
                    ->label('Work shift'),
                TextEntry::make('effective_from')
                    ->date(),
                TextEntry::make('effective_to')
                    ->date()
                    ->placeholder('-'),
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
                    ->visible(fn (ShiftAssignment $record): bool => $record->trashed()),
            ]);
    }
}
