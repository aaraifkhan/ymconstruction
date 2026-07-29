<?php

namespace App\Filament\Resources\PayrollCalculationRules;

use App\Filament\Resources\PayrollCalculationRules\Pages\CreatePayrollCalculationRule;
use App\Filament\Resources\PayrollCalculationRules\Pages\EditPayrollCalculationRule;
use App\Filament\Resources\PayrollCalculationRules\Pages\ListPayrollCalculationRules;
use App\Filament\Resources\PayrollCalculationRules\Pages\ViewPayrollCalculationRule;
use App\Filament\Resources\PayrollCalculationRules\Schemas\PayrollCalculationRuleForm;
use App\Filament\Resources\PayrollCalculationRules\Schemas\PayrollCalculationRuleInfolist;
use App\Filament\Resources\PayrollCalculationRules\Tables\PayrollCalculationRulesTable;
use App\Models\PayrollCalculationRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollCalculationRuleResource extends Resource
{
    protected static ?string $model = PayrollCalculationRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $tenantRelationshipName = 'payrollCalculationRules';

    protected static \UnitEnum|string|null $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Calculation Rules';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PayrollCalculationRuleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PayrollCalculationRuleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollCalculationRulesTable::configure($table);
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
            'index' => ListPayrollCalculationRules::route('/'),
            'create' => CreatePayrollCalculationRule::route('/create'),
            'view' => ViewPayrollCalculationRule::route('/{record}'),
            'edit' => EditPayrollCalculationRule::route('/{record}/edit'),
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
