<?php

namespace App\Filament\Resources\AttendanceRawEvents\Schemas;

use App\Models\AttendanceRawEvent;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceRawEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('attendanceDevice.name')
                    ->label('Attendance device'),
                TextEntry::make('attendance_import_batch_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('attendance_device_user_mapping_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('employment.id')
                    ->label('Employment')
                    ->placeholder('-'),
                TextEntry::make('external_user_id'),
                TextEntry::make('original_punched_at_local'),
                TextEntry::make('timezone'),
                TextEntry::make('punched_at_utc')
                    ->dateTime(),
                TextEntry::make('direction')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('source_event_id')
                    ->placeholder('-'),
                TextEntry::make('safe_payload')
                    ->placeholder('-')
                    ->formatStateUsing(fn (mixed $state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                    ->visible(fn (AttendanceRawEvent $record): bool => auth()->user()?->can('viewPayload', $record) ?? false)
                    ->columnSpanFull(),
                TextEntry::make('event_fingerprint'),
                TextEntry::make('processing_status')
                    ->badge(),
                TextEntry::make('processing_error')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('received_at')
                    ->dateTime(),
                TextEntry::make('processed_at')
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
