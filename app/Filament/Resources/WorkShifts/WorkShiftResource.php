<?php

namespace App\Filament\Resources\WorkShifts;

use App\Filament\Resources\WorkShifts\Pages\CreateWorkShift;
use App\Filament\Resources\WorkShifts\Pages\EditWorkShift;
use App\Filament\Resources\WorkShifts\Pages\ListWorkShifts;
use App\Filament\Resources\WorkShifts\Pages\ViewWorkShift;
use App\Filament\Resources\WorkShifts\Schemas\WorkShiftForm;
use App\Filament\Resources\WorkShifts\Schemas\WorkShiftInfolist;
use App\Filament\Resources\WorkShifts\Tables\WorkShiftsTable;
use App\Models\WorkShift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkShiftResource extends Resource
{
    protected static ?string $model = WorkShift::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $tenantRelationshipName = 'workShifts';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return WorkShiftForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WorkShiftInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkShiftsTable::configure($table);
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
            'index' => ListWorkShifts::route('/'),
            'create' => CreateWorkShift::route('/create'),
            'view' => ViewWorkShift::route('/{record}'),
            'edit' => EditWorkShift::route('/{record}/edit'),
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
