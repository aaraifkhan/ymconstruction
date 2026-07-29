<?php

namespace App\Filament\Resources\AttendanceDevices\Schemas;

use App\Models\AttendanceDevice;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceDeviceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('workLocation.name')
                    ->label('Work location')
                    ->placeholder('-'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('device_identifier'),
                TextEntry::make('timezone'),
                TextEntry::make('transport')
                    ->badge(),
                TextEntry::make('connection_profile_reference')
                    ->placeholder('-'),
                TextEntry::make('health_status')
                    ->badge(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('last_sync_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('last_seen_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('last_cursor')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('last_error_summary')
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
                    ->visible(fn (AttendanceDevice $record): bool => $record->trashed()),
            ]);
    }
}
