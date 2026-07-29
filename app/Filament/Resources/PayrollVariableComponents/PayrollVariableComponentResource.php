<?php

namespace App\Filament\Resources\PayrollVariableComponents;

use App\Filament\Resources\PayrollVariableComponents\Pages\CreatePayrollVariableComponent;
use App\Filament\Resources\PayrollVariableComponents\Pages\EditPayrollVariableComponent;
use App\Filament\Resources\PayrollVariableComponents\Pages\ListPayrollVariableComponents;
use App\Filament\Resources\PayrollVariableComponents\Pages\ViewPayrollVariableComponent;
use App\Filament\Resources\PayrollVariableComponents\Schemas\PayrollVariableComponentForm;
use App\Filament\Resources\PayrollVariableComponents\Schemas\PayrollVariableComponentInfolist;
use App\Filament\Resources\PayrollVariableComponents\Tables\PayrollVariableComponentsTable;
use App\Models\PayrollVariableComponent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollVariableComponentResource extends Resource
{
    protected static ?string $model = PayrollVariableComponent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static ?string $tenantRelationshipName = 'payrollVariableComponents';

    protected static \UnitEnum|string|null $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Bonus & Incentives';

    protected static ?string $recordTitleAttribute = 'source_reference';

    public static function form(Schema $schema): Schema
    {
        return PayrollVariableComponentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PayrollVariableComponentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollVariableComponentsTable::configure($table);
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
            'index' => ListPayrollVariableComponents::route('/'),
            'create' => CreatePayrollVariableComponent::route('/create'),
            'view' => ViewPayrollVariableComponent::route('/{record}'),
            'edit' => EditPayrollVariableComponent::route('/{record}/edit'),
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
