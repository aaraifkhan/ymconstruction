<?php

namespace App\Filament\Resources\AttendanceRawEvents;

use App\Filament\Resources\AttendanceRawEvents\Pages\ListAttendanceRawEvents;
use App\Filament\Resources\AttendanceRawEvents\Pages\ViewAttendanceRawEvent;
use App\Filament\Resources\AttendanceRawEvents\Schemas\AttendanceRawEventForm;
use App\Filament\Resources\AttendanceRawEvents\Schemas\AttendanceRawEventInfolist;
use App\Filament\Resources\AttendanceRawEvents\Tables\AttendanceRawEventsTable;
use App\Models\AttendanceRawEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendanceRawEventResource extends Resource
{
    protected static ?string $model = AttendanceRawEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'attendanceRawEvents';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return AttendanceRawEventForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceRawEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceRawEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceRawEvents::route('/'),
            'view' => ViewAttendanceRawEvent::route('/{record}'),
        ];
    }
}
