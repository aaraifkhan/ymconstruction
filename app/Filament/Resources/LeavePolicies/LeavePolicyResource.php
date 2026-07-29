<?php

namespace App\Filament\Resources\LeavePolicies;

use App\Filament\Resources\LeavePolicies\Pages\CreateLeavePolicy;
use App\Filament\Resources\LeavePolicies\Pages\EditLeavePolicy;
use App\Filament\Resources\LeavePolicies\Pages\ListLeavePolicies;
use App\Filament\Resources\LeavePolicies\Pages\ViewLeavePolicy;
use App\Filament\Resources\LeavePolicies\Schemas\LeavePolicyForm;
use App\Filament\Resources\LeavePolicies\Schemas\LeavePolicyInfolist;
use App\Filament\Resources\LeavePolicies\Tables\LeavePoliciesTable;
use App\Models\LeavePolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeavePolicyResource extends Resource
{
    protected static ?string $model = LeavePolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'leavePolicies';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return LeavePolicyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeavePolicyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeavePoliciesTable::configure($table);
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
            'index' => ListLeavePolicies::route('/'),
            'create' => CreateLeavePolicy::route('/create'),
            'view' => ViewLeavePolicy::route('/{record}'),
            'edit' => EditLeavePolicy::route('/{record}/edit'),
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
