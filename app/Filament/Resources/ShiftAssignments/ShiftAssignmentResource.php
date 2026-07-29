<?php

namespace App\Filament\Resources\ShiftAssignments;

use App\Filament\Resources\ShiftAssignments\Pages\CreateShiftAssignment;
use App\Filament\Resources\ShiftAssignments\Pages\EditShiftAssignment;
use App\Filament\Resources\ShiftAssignments\Pages\ListShiftAssignments;
use App\Filament\Resources\ShiftAssignments\Pages\ViewShiftAssignment;
use App\Filament\Resources\ShiftAssignments\Schemas\ShiftAssignmentForm;
use App\Filament\Resources\ShiftAssignments\Schemas\ShiftAssignmentInfolist;
use App\Filament\Resources\ShiftAssignments\Tables\ShiftAssignmentsTable;
use App\Models\ShiftAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShiftAssignmentResource extends Resource
{
    protected static ?string $model = ShiftAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'shiftAssignments';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return ShiftAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ShiftAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShiftAssignmentsTable::configure($table);
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
            'index' => ListShiftAssignments::route('/'),
            'create' => CreateShiftAssignment::route('/create'),
            'view' => ViewShiftAssignment::route('/{record}'),
            'edit' => EditShiftAssignment::route('/{record}/edit'),
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
