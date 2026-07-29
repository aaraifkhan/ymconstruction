<?php

namespace App\Filament\Resources\WorkShifts\Schemas;

use App\Models\WorkShift;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WorkShiftInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('starts_at')
                    ->time(),
                TextEntry::make('ends_at')
                    ->time(),
                TextEntry::make('break_minutes')
                    ->numeric(),
                IconEntry::make('is_overnight')
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
                    ->visible(fn (WorkShift $record): bool => $record->trashed()),
            ]);
    }
}
