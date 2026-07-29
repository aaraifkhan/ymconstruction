<?php

namespace App\Filament\Resources\AttendanceDeviceUserMappings\Schemas;

use App\Models\AttendanceDeviceUserMapping;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceDeviceUserMappingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('attendanceDevice.name')
                    ->label('Attendance device'),
                TextEntry::make('employment.id')
                    ->label('Employment'),
                TextEntry::make('external_user_id'),
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
                    ->visible(fn (AttendanceDeviceUserMapping $record): bool => $record->trashed()),
            ]);
    }
}
