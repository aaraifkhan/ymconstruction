<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('employment.id')
                    ->label('Employment'),
                TextEntry::make('shiftAssignment.id')
                    ->label('Shift assignment')
                    ->placeholder('-'),
                TextEntry::make('attendanceRule.name')
                    ->label('Attendance rule')
                    ->placeholder('-'),
                TextEntry::make('attendance_date')
                    ->date(),
                TextEntry::make('day_status')
                    ->badge(),
                TextEntry::make('state')
                    ->badge(),
                TextEntry::make('first_in_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('last_out_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('scheduled_minutes')
                    ->numeric(),
                TextEntry::make('worked_minutes')
                    ->numeric(),
                TextEntry::make('late_minutes')
                    ->numeric(),
                TextEntry::make('overtime_minutes')
                    ->numeric(),
                TextEntry::make('source_checksum')
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('finalizedBy.name')
                    ->label('Finalized by')
                    ->placeholder('-'),
                TextEntry::make('finalized_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
