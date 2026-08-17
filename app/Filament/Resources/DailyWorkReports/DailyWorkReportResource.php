<?php

namespace App\Filament\Resources\DailyWorkReports;

use App\Filament\Resources\DailyWorkReports\Pages\CreateDailyWorkReport;
use App\Filament\Resources\DailyWorkReports\Pages\EditDailyWorkReport;
use App\Filament\Resources\DailyWorkReports\Pages\ListDailyWorkReports;
use App\Filament\Resources\DailyWorkReports\Pages\ViewDailyWorkReport;
use App\Filament\Resources\DailyWorkReports\Schemas\DailyWorkReportForm;
use App\Filament\Resources\DailyWorkReports\Schemas\DailyWorkReportInfolist;
use App\Filament\Resources\DailyWorkReports\Tables\DailyWorkReportsTable;
use App\Models\DailyWorkReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DailyWorkReportResource extends Resource
{
    protected static ?string $model = DailyWorkReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'report_date';

    protected static ?string $tenantRelationshipName = 'dailyWorkReports';

    protected static \UnitEnum|string|null $navigationGroup = 'Department Operations';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return DailyWorkReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DailyWorkReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyWorkReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyWorkReports::route('/'),
            'create' => CreateDailyWorkReport::route('/create'),
            'view' => ViewDailyWorkReport::route('/{record}'),
            'edit' => EditDailyWorkReport::route('/{record}/edit'),
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
