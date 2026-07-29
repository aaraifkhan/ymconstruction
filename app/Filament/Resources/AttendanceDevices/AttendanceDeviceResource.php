<?php

namespace App\Filament\Resources\AttendanceDevices;

use App\Filament\Resources\AttendanceDevices\Pages\CreateAttendanceDevice;
use App\Filament\Resources\AttendanceDevices\Pages\EditAttendanceDevice;
use App\Filament\Resources\AttendanceDevices\Pages\ListAttendanceDevices;
use App\Filament\Resources\AttendanceDevices\Pages\ViewAttendanceDevice;
use App\Filament\Resources\AttendanceDevices\Schemas\AttendanceDeviceForm;
use App\Filament\Resources\AttendanceDevices\Schemas\AttendanceDeviceInfolist;
use App\Filament\Resources\AttendanceDevices\Tables\AttendanceDevicesTable;
use App\Models\AttendanceDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceDeviceResource extends Resource
{
    protected static ?string $model = AttendanceDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $tenantRelationshipName = 'attendanceDevices';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AttendanceDeviceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceDeviceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceDevicesTable::configure($table);
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
            'index' => ListAttendanceDevices::route('/'),
            'create' => CreateAttendanceDevice::route('/create'),
            'view' => ViewAttendanceDevice::route('/{record}'),
            'edit' => EditAttendanceDevice::route('/{record}/edit'),
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
