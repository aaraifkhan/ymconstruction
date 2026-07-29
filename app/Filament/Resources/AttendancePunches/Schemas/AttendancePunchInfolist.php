<?php

namespace App\Filament\Resources\AttendancePunches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendancePunchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('employment.id')
                    ->label('Employment'),
                TextEntry::make('punched_at')
                    ->dateTime(),
                TextEntry::make('direction')
                    ->badge(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('reason')
                    ->columnSpanFull(),
                TextEntry::make('createdBy.name')
                    ->label('Created by'),
                TextEntry::make('approvedBy.name')
                    ->label('Approved by')
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('rejection_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
