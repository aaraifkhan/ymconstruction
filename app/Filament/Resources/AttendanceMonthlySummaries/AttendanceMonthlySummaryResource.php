<?php

namespace App\Filament\Resources\AttendanceMonthlySummaries;

use App\Filament\Resources\AttendanceMonthlySummaries\Pages\CreateAttendanceMonthlySummary;
use App\Filament\Resources\AttendanceMonthlySummaries\Pages\EditAttendanceMonthlySummary;
use App\Filament\Resources\AttendanceMonthlySummaries\Pages\ListAttendanceMonthlySummaries;
use App\Filament\Resources\AttendanceMonthlySummaries\Pages\ViewAttendanceMonthlySummary;
use App\Filament\Resources\AttendanceMonthlySummaries\Schemas\AttendanceMonthlySummaryForm;
use App\Filament\Resources\AttendanceMonthlySummaries\Schemas\AttendanceMonthlySummaryInfolist;
use App\Filament\Resources\AttendanceMonthlySummaries\Tables\AttendanceMonthlySummariesTable;
use App\Models\AttendanceMonthlySummary;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendanceMonthlySummaryResource extends Resource
{
    protected static ?string $model = AttendanceMonthlySummary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'attendanceMonthlySummaries';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return AttendanceMonthlySummaryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceMonthlySummaryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceMonthlySummariesTable::configure($table);
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
            'index' => ListAttendanceMonthlySummaries::route('/'),
            'create' => CreateAttendanceMonthlySummary::route('/create'),
            'view' => ViewAttendanceMonthlySummary::route('/{record}'),
            'edit' => EditAttendanceMonthlySummary::route('/{record}/edit'),
        ];
    }
}
