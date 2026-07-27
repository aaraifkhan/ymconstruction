<?php

namespace App\Filament\Resources\CompanyModules;

use App\Filament\Resources\CompanyModules\Pages\CreateCompanyModule;
use App\Filament\Resources\CompanyModules\Pages\EditCompanyModule;
use App\Filament\Resources\CompanyModules\Pages\ListCompanyModules;
use App\Filament\Resources\CompanyModules\Pages\ViewCompanyModule;
use App\Filament\Resources\CompanyModules\Schemas\CompanyModuleForm;
use App\Filament\Resources\CompanyModules\Schemas\CompanyModuleInfolist;
use App\Filament\Resources\CompanyModules\Tables\CompanyModulesTable;
use App\Models\CompanyModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyModuleResource extends Resource
{
    protected static ?string $model = CompanyModule::class;

    protected static ?string $tenantRelationshipName = 'companyModules';

    protected static \UnitEnum|string|null $navigationGroup = 'Company Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function form(Schema $schema): Schema
    {
        return CompanyModuleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompanyModuleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyModulesTable::configure($table);
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
            'index' => ListCompanyModules::route('/'),
            'create' => CreateCompanyModule::route('/create'),
            'view' => ViewCompanyModule::route('/{record}'),
            'edit' => EditCompanyModule::route('/{record}/edit'),
        ];
    }
}
