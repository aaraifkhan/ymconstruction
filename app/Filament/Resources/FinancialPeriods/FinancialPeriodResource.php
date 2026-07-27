<?php

namespace App\Filament\Resources\FinancialPeriods;

use App\Filament\Resources\FinancialPeriods\Pages\CreateFinancialPeriod;
use App\Filament\Resources\FinancialPeriods\Pages\EditFinancialPeriod;
use App\Filament\Resources\FinancialPeriods\Pages\ListFinancialPeriods;
use App\Filament\Resources\FinancialPeriods\Pages\ViewFinancialPeriod;
use App\Filament\Resources\FinancialPeriods\Schemas\FinancialPeriodForm;
use App\Filament\Resources\FinancialPeriods\Schemas\FinancialPeriodInfolist;
use App\Filament\Resources\FinancialPeriods\Tables\FinancialPeriodsTable;
use App\Models\FinancialPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FinancialPeriodResource extends Resource
{
    protected static ?string $model = FinancialPeriod::class;

    protected static ?string $tenantRelationshipName = 'financialPeriods';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return FinancialPeriodForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FinancialPeriodInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialPeriodsTable::configure($table);
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
            'index' => ListFinancialPeriods::route('/'),
            'create' => CreateFinancialPeriod::route('/create'),
            'view' => ViewFinancialPeriod::route('/{record}'),
            'edit' => EditFinancialPeriod::route('/{record}/edit'),
        ];
    }
}
