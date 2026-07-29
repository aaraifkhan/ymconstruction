<?php

namespace App\Filament\Resources\AttendanceDeviceUserMappings;

use App\Filament\Resources\AttendanceDeviceUserMappings\Pages\CreateAttendanceDeviceUserMapping;
use App\Filament\Resources\AttendanceDeviceUserMappings\Pages\EditAttendanceDeviceUserMapping;
use App\Filament\Resources\AttendanceDeviceUserMappings\Pages\ListAttendanceDeviceUserMappings;
use App\Filament\Resources\AttendanceDeviceUserMappings\Pages\ViewAttendanceDeviceUserMapping;
use App\Filament\Resources\AttendanceDeviceUserMappings\Schemas\AttendanceDeviceUserMappingForm;
use App\Filament\Resources\AttendanceDeviceUserMappings\Schemas\AttendanceDeviceUserMappingInfolist;
use App\Filament\Resources\AttendanceDeviceUserMappings\Tables\AttendanceDeviceUserMappingsTable;
use App\Models\AttendanceDeviceUserMapping;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceDeviceUserMappingResource extends Resource
{
    protected static ?string $model = AttendanceDeviceUserMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'attendanceDeviceUserMappings';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    protected static ?string $recordTitleAttribute = 'external_user_id';

    public static function form(Schema $schema): Schema
    {
        return AttendanceDeviceUserMappingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceDeviceUserMappingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceDeviceUserMappingsTable::configure($table);
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
            'index' => ListAttendanceDeviceUserMappings::route('/'),
            'create' => CreateAttendanceDeviceUserMapping::route('/create'),
            'view' => ViewAttendanceDeviceUserMapping::route('/{record}'),
            'edit' => EditAttendanceDeviceUserMapping::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
