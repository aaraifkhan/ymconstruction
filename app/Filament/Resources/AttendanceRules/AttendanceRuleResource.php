<?php

namespace App\Filament\Resources\AttendanceRules;

use App\Filament\Resources\AttendanceRules\Pages\CreateAttendanceRule;
use App\Filament\Resources\AttendanceRules\Pages\EditAttendanceRule;
use App\Filament\Resources\AttendanceRules\Pages\ListAttendanceRules;
use App\Filament\Resources\AttendanceRules\Pages\ViewAttendanceRule;
use App\Filament\Resources\AttendanceRules\Schemas\AttendanceRuleForm;
use App\Filament\Resources\AttendanceRules\Schemas\AttendanceRuleInfolist;
use App\Filament\Resources\AttendanceRules\Tables\AttendanceRulesTable;
use App\Models\AttendanceRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceRuleResource extends Resource
{
    protected static ?string $model = AttendanceRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $tenantRelationshipName = 'attendanceRules';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return AttendanceRuleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceRuleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceRulesTable::configure($table);
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
            'index' => ListAttendanceRules::route('/'),
            'create' => CreateAttendanceRule::route('/create'),
            'view' => ViewAttendanceRule::route('/{record}'),
            'edit' => EditAttendanceRule::route('/{record}/edit'),
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
