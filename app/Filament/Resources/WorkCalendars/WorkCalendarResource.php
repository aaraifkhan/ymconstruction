<?php

namespace App\Filament\Resources\WorkCalendars;

use App\Filament\Resources\WorkCalendars\Pages\CreateWorkCalendar;
use App\Filament\Resources\WorkCalendars\Pages\EditWorkCalendar;
use App\Filament\Resources\WorkCalendars\Pages\ListWorkCalendars;
use App\Filament\Resources\WorkCalendars\Pages\ViewWorkCalendar;
use App\Filament\Resources\WorkCalendars\Schemas\WorkCalendarForm;
use App\Filament\Resources\WorkCalendars\Schemas\WorkCalendarInfolist;
use App\Filament\Resources\WorkCalendars\Tables\WorkCalendarsTable;
use App\Models\WorkCalendar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkCalendarResource extends Resource
{
    protected static ?string $model = WorkCalendar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $tenantRelationshipName = 'workCalendars';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return WorkCalendarForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WorkCalendarInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkCalendarsTable::configure($table);
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
            'index' => ListWorkCalendars::route('/'),
            'create' => CreateWorkCalendar::route('/create'),
            'view' => ViewWorkCalendar::route('/{record}'),
            'edit' => EditWorkCalendar::route('/{record}/edit'),
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
