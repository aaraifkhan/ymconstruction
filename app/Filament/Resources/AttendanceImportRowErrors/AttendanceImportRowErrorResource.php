<?php

namespace App\Filament\Resources\AttendanceImportRowErrors;

use App\Filament\Resources\AttendanceImportRowErrors\Pages\ListAttendanceImportRowErrors;
use App\Filament\Resources\AttendanceImportRowErrors\Pages\ViewAttendanceImportRowError;
use App\Filament\Resources\AttendanceImportRowErrors\Schemas\AttendanceImportRowErrorForm;
use App\Filament\Resources\AttendanceImportRowErrors\Schemas\AttendanceImportRowErrorInfolist;
use App\Filament\Resources\AttendanceImportRowErrors\Tables\AttendanceImportRowErrorsTable;
use App\Models\AttendanceImportRowError;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendanceImportRowErrorResource extends Resource
{
    protected static ?string $model = AttendanceImportRowError::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'attendanceImportRowErrors';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return AttendanceImportRowErrorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceImportRowErrorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceImportRowErrorsTable::configure($table);
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
            'index' => ListAttendanceImportRowErrors::route('/'),
            'view' => ViewAttendanceImportRowError::route('/{record}'),
        ];
    }
}
