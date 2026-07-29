<?php

namespace App\Filament\Resources\AttendanceImportBatches;

use App\Filament\Resources\AttendanceImportBatches\Pages\ListAttendanceImportBatches;
use App\Filament\Resources\AttendanceImportBatches\Pages\ViewAttendanceImportBatch;
use App\Filament\Resources\AttendanceImportBatches\Schemas\AttendanceImportBatchForm;
use App\Filament\Resources\AttendanceImportBatches\Schemas\AttendanceImportBatchInfolist;
use App\Filament\Resources\AttendanceImportBatches\Tables\AttendanceImportBatchesTable;
use App\Models\AttendanceImportBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendanceImportBatchResource extends Resource
{
    protected static ?string $model = AttendanceImportBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'attendanceImportBatches';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    protected static ?string $recordTitleAttribute = 'batch_checksum';

    public static function form(Schema $schema): Schema
    {
        return AttendanceImportBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceImportBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceImportBatchesTable::configure($table);
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
            'index' => ListAttendanceImportBatches::route('/'),
            'view' => ViewAttendanceImportBatch::route('/{record}'),
        ];
    }
}
