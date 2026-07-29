<?php

namespace App\Filament\Resources\AttendanceCorrections;

use App\Filament\Resources\AttendanceCorrections\Pages\CreateAttendanceCorrection;
use App\Filament\Resources\AttendanceCorrections\Pages\EditAttendanceCorrection;
use App\Filament\Resources\AttendanceCorrections\Pages\ListAttendanceCorrections;
use App\Filament\Resources\AttendanceCorrections\Pages\ViewAttendanceCorrection;
use App\Filament\Resources\AttendanceCorrections\Schemas\AttendanceCorrectionForm;
use App\Filament\Resources\AttendanceCorrections\Schemas\AttendanceCorrectionInfolist;
use App\Filament\Resources\AttendanceCorrections\Tables\AttendanceCorrectionsTable;
use App\Models\AttendanceCorrection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendanceCorrectionResource extends Resource
{
    protected static ?string $model = AttendanceCorrection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'attendanceCorrections';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return AttendanceCorrectionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceCorrectionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceCorrectionsTable::configure($table);
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
            'index' => ListAttendanceCorrections::route('/'),
            'create' => CreateAttendanceCorrection::route('/create'),
            'view' => ViewAttendanceCorrection::route('/{record}'),
            'edit' => EditAttendanceCorrection::route('/{record}/edit'),
        ];
    }
}
