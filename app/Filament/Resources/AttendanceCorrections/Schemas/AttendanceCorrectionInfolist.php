<?php

namespace App\Filament\Resources\AttendanceCorrections\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceCorrectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('attendanceRecord.id')
                    ->label('Attendance record'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('before_snapshot')
                    ->columnSpanFull(),
                TextEntry::make('proposed_snapshot')
                    ->columnSpanFull(),
                TextEntry::make('reason')
                    ->columnSpanFull(),
                TextEntry::make('requestedBy.name')
                    ->label('Requested by'),
                TextEntry::make('decidedBy.name')
                    ->label('Decided by')
                    ->placeholder('-'),
                TextEntry::make('decided_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('decision_reason')
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
