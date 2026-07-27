<?php

namespace App\Filament\Resources\EmploymentCompensation;

use App\Filament\Resources\EmploymentCompensation\Pages\CreateEmploymentCompensation;
use App\Filament\Resources\EmploymentCompensation\Pages\EditEmploymentCompensation;
use App\Filament\Resources\EmploymentCompensation\Pages\ListEmploymentCompensation;
use App\Filament\Resources\EmploymentCompensation\Pages\ViewEmploymentCompensation;
use App\Filament\Resources\EmploymentCompensation\Schemas\EmploymentCompensationForm;
use App\Filament\Resources\EmploymentCompensation\Schemas\EmploymentCompensationInfolist;
use App\Filament\Resources\EmploymentCompensation\Tables\EmploymentCompensationTable;
use App\Models\EmploymentCompensation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmploymentCompensationResource extends Resource
{
    protected static ?string $model = EmploymentCompensation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'effective_from';

    protected static ?string $tenantRelationshipName = 'employmentCompensations';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    protected static ?string $navigationLabel = 'Compensation History';

    protected static ?string $modelLabel = 'Employment Compensation';

    protected static ?string $pluralModelLabel = 'Compensation History';

    public static function form(Schema $schema): Schema
    {
        return EmploymentCompensationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmploymentCompensationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmploymentCompensationTable::configure($table);
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
            'index' => ListEmploymentCompensation::route('/'),
            'create' => CreateEmploymentCompensation::route('/create'),
            'view' => ViewEmploymentCompensation::route('/{record}'),
            'edit' => EditEmploymentCompensation::route('/{record}/edit'),
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
