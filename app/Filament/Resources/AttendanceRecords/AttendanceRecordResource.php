<?php

namespace App\Filament\Resources\AttendanceRecords;

use App\Filament\Resources\AttendanceRecords\Pages\CreateAttendanceRecord;
use App\Filament\Resources\AttendanceRecords\Pages\EditAttendanceRecord;
use App\Filament\Resources\AttendanceRecords\Pages\ListAttendanceRecords;
use App\Filament\Resources\AttendanceRecords\Pages\ViewAttendanceRecord;
use App\Filament\Resources\AttendanceRecords\Schemas\AttendanceRecordForm;
use App\Filament\Resources\AttendanceRecords\Schemas\AttendanceRecordInfolist;
use App\Filament\Resources\AttendanceRecords\Tables\AttendanceRecordsTable;
use App\Models\AttendanceRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'attendanceRecords';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return AttendanceRecordForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceRecordsTable::configure($table);
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
            'index' => ListAttendanceRecords::route('/'),
            'create' => CreateAttendanceRecord::route('/create'),
            'view' => ViewAttendanceRecord::route('/{record}'),
            'edit' => EditAttendanceRecord::route('/{record}/edit'),
        ];
    }
}
