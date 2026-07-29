<?php

namespace App\Filament\Resources\AttendancePunches;

use App\Filament\Resources\AttendancePunches\Pages\CreateAttendancePunch;
use App\Filament\Resources\AttendancePunches\Pages\EditAttendancePunch;
use App\Filament\Resources\AttendancePunches\Pages\ListAttendancePunches;
use App\Filament\Resources\AttendancePunches\Pages\ViewAttendancePunch;
use App\Filament\Resources\AttendancePunches\Schemas\AttendancePunchForm;
use App\Filament\Resources\AttendancePunches\Schemas\AttendancePunchInfolist;
use App\Filament\Resources\AttendancePunches\Tables\AttendancePunchesTable;
use App\Models\AttendancePunch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendancePunchResource extends Resource
{
    protected static ?string $model = AttendancePunch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?string $tenantRelationshipName = 'attendancePunches';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return AttendancePunchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendancePunchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendancePunchesTable::configure($table);
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
            'index' => ListAttendancePunches::route('/'),
            'create' => CreateAttendancePunch::route('/create'),
            'view' => ViewAttendancePunch::route('/{record}'),
            'edit' => EditAttendancePunch::route('/{record}/edit'),
        ];
    }
}
