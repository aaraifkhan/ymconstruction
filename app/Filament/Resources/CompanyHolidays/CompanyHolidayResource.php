<?php

namespace App\Filament\Resources\CompanyHolidays;

use App\Filament\Resources\CompanyHolidays\Pages\CreateCompanyHoliday;
use App\Filament\Resources\CompanyHolidays\Pages\EditCompanyHoliday;
use App\Filament\Resources\CompanyHolidays\Pages\ListCompanyHolidays;
use App\Filament\Resources\CompanyHolidays\Pages\ViewCompanyHoliday;
use App\Filament\Resources\CompanyHolidays\Schemas\CompanyHolidayForm;
use App\Filament\Resources\CompanyHolidays\Schemas\CompanyHolidayInfolist;
use App\Filament\Resources\CompanyHolidays\Tables\CompanyHolidaysTable;
use App\Models\CompanyHoliday;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyHolidayResource extends Resource
{
    protected static ?string $model = CompanyHoliday::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $tenantRelationshipName = 'companyHolidays';

    protected static \UnitEnum|string|null $navigationGroup = 'Attendance & Leave';

    public static function form(Schema $schema): Schema
    {
        return CompanyHolidayForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompanyHolidayInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyHolidaysTable::configure($table);
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
            'index' => ListCompanyHolidays::route('/'),
            'create' => CreateCompanyHoliday::route('/create'),
            'view' => ViewCompanyHoliday::route('/{record}'),
            'edit' => EditCompanyHoliday::route('/{record}/edit'),
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
