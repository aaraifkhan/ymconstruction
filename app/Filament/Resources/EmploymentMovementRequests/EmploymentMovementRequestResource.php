<?php

namespace App\Filament\Resources\EmploymentMovementRequests;

use App\Filament\Resources\EmploymentMovementRequests\Pages\CreateEmploymentMovementRequest;
use App\Filament\Resources\EmploymentMovementRequests\Pages\EditEmploymentMovementRequest;
use App\Filament\Resources\EmploymentMovementRequests\Pages\ListEmploymentMovementRequests;
use App\Filament\Resources\EmploymentMovementRequests\Pages\ViewEmploymentMovementRequest;
use App\Filament\Resources\EmploymentMovementRequests\Schemas\EmploymentMovementRequestForm;
use App\Filament\Resources\EmploymentMovementRequests\Schemas\EmploymentMovementRequestInfolist;
use App\Filament\Resources\EmploymentMovementRequests\Tables\EmploymentMovementRequestsTable;
use App\Models\EmploymentMovementRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmploymentMovementRequestResource extends Resource
{
    protected static ?string $model = EmploymentMovementRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static ?string $tenantRelationshipName = 'employmentMovementRequests';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return EmploymentMovementRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmploymentMovementRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmploymentMovementRequestsTable::configure($table);
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
            'index' => ListEmploymentMovementRequests::route('/'),
            'create' => CreateEmploymentMovementRequest::route('/create'),
            'view' => ViewEmploymentMovementRequest::route('/{record}'),
            'edit' => EditEmploymentMovementRequest::route('/{record}/edit'),
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
