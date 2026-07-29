<?php

namespace App\Filament\Resources\AppraisalCycles;

use App\Filament\Resources\AppraisalCycles\Pages\CreateAppraisalCycle;
use App\Filament\Resources\AppraisalCycles\Pages\EditAppraisalCycle;
use App\Filament\Resources\AppraisalCycles\Pages\ListAppraisalCycles;
use App\Filament\Resources\AppraisalCycles\Pages\ViewAppraisalCycle;
use App\Filament\Resources\AppraisalCycles\Schemas\AppraisalCycleForm;
use App\Filament\Resources\AppraisalCycles\Schemas\AppraisalCycleInfolist;
use App\Filament\Resources\AppraisalCycles\Tables\AppraisalCyclesTable;
use App\Models\AppraisalCycle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppraisalCycleResource extends Resource
{
    protected static ?string $model = AppraisalCycle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'appraisalCycles';

    protected static \UnitEnum|string|null $navigationGroup = 'HR Management';

    public static function form(Schema $schema): Schema
    {
        return AppraisalCycleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppraisalCycleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppraisalCyclesTable::configure($table);
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
            'index' => ListAppraisalCycles::route('/'),
            'create' => CreateAppraisalCycle::route('/create'),
            'view' => ViewAppraisalCycle::route('/{record}'),
            'edit' => EditAppraisalCycle::route('/{record}/edit'),
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
