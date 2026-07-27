<?php

namespace App\Filament\Resources\PayrollAccountMappings;

use App\Filament\Resources\PayrollAccountMappings\Pages\CreatePayrollAccountMapping;
use App\Filament\Resources\PayrollAccountMappings\Pages\EditPayrollAccountMapping;
use App\Filament\Resources\PayrollAccountMappings\Pages\ListPayrollAccountMappings;
use App\Filament\Resources\PayrollAccountMappings\Pages\ViewPayrollAccountMapping;
use App\Filament\Resources\PayrollAccountMappings\Schemas\PayrollAccountMappingForm;
use App\Filament\Resources\PayrollAccountMappings\Schemas\PayrollAccountMappingInfolist;
use App\Filament\Resources\PayrollAccountMappings\Tables\PayrollAccountMappingsTable;
use App\Models\PayrollAccountMapping;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayrollAccountMappingResource extends Resource
{
    protected static ?string $model = PayrollAccountMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $tenantRelationshipName = 'payrollAccountMappings';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    public static function form(Schema $schema): Schema
    {
        return PayrollAccountMappingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PayrollAccountMappingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollAccountMappingsTable::configure($table);
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
            'index' => ListPayrollAccountMappings::route('/'),
            'create' => CreatePayrollAccountMapping::route('/create'),
            'view' => ViewPayrollAccountMapping::route('/{record}'),
            'edit' => EditPayrollAccountMapping::route('/{record}/edit'),
        ];
    }
}
