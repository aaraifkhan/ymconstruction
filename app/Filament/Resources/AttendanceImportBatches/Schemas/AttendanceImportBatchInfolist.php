<?php

namespace App\Filament\Resources\AttendanceImportBatches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceImportBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('attendanceDevice.name')
                    ->label('Attendance device')
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->badge(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('original_filename')
                    ->placeholder('-'),
                TextEntry::make('stored_file_path')
                    ->placeholder('-'),
                TextEntry::make('batch_checksum'),
                TextEntry::make('cursor_before')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('cursor_after')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('source_metadata')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('row_count')
                    ->numeric(),
                TextEntry::make('accepted_count')
                    ->numeric(),
                TextEntry::make('duplicate_count')
                    ->numeric(),
                TextEntry::make('quarantined_count')
                    ->numeric(),
                TextEntry::make('error_count')
                    ->numeric(),
                TextEntry::make('initiatedBy.name')
                    ->label('Initiated by')
                    ->placeholder('-'),
                TextEntry::make('started_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('failure_summary')
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
