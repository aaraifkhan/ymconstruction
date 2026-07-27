<?php

namespace App\Filament\Resources\AccountingSettings;

use App\Filament\Resources\AccountingSettings\Pages\CreateAccountingSetting;
use App\Filament\Resources\AccountingSettings\Pages\EditAccountingSetting;
use App\Filament\Resources\AccountingSettings\Pages\ListAccountingSettings;
use App\Filament\Resources\AccountingSettings\Pages\ViewAccountingSetting;
use App\Filament\Resources\AccountingSettings\Schemas\AccountingSettingForm;
use App\Filament\Resources\AccountingSettings\Schemas\AccountingSettingInfolist;
use App\Filament\Resources\AccountingSettings\Tables\AccountingSettingsTable;
use App\Models\AccountingSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AccountingSettingResource extends Resource
{
    protected static ?string $model = AccountingSetting::class;

    protected static ?string $tenantRelationshipName = 'accountingSettings';

    protected static \UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog8Tooth;

    public static function form(Schema $schema): Schema
    {
        return AccountingSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccountingSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountingSettingsTable::configure($table);
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
            'index' => ListAccountingSettings::route('/'),
            'create' => CreateAccountingSetting::route('/create'),
            'view' => ViewAccountingSetting::route('/{record}'),
            'edit' => EditAccountingSetting::route('/{record}/edit'),
        ];
    }
}
