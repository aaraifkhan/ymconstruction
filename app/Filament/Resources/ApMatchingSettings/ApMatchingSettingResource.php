<?php

namespace App\Filament\Resources\ApMatchingSettings;

use App\Filament\Resources\ApMatchingSettings\Pages\CreateApMatchingSetting;
use App\Filament\Resources\ApMatchingSettings\Pages\EditApMatchingSetting;
use App\Filament\Resources\ApMatchingSettings\Pages\ListApMatchingSettings;
use App\Filament\Resources\ApMatchingSettings\Pages\ViewApMatchingSetting;
use App\Filament\Resources\ApMatchingSettings\Schemas\ApMatchingSettingForm;
use App\Filament\Resources\ApMatchingSettings\Schemas\ApMatchingSettingInfolist;
use App\Filament\Resources\ApMatchingSettings\Tables\ApMatchingSettingsTable;
use App\Models\ApMatchingSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApMatchingSettingResource extends Resource
{
    protected static ?string $model = ApMatchingSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $tenantRelationshipName = 'apMatchingSettings';

    protected static \UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'AP Match Tolerances';

    public static function form(Schema $schema): Schema
    {
        return ApMatchingSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ApMatchingSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApMatchingSettingsTable::configure($table);
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
            'index' => ListApMatchingSettings::route('/'),
            'create' => CreateApMatchingSetting::route('/create'),
            'view' => ViewApMatchingSetting::route('/{record}'),
            'edit' => EditApMatchingSetting::route('/{record}/edit'),
        ];
    }
}
