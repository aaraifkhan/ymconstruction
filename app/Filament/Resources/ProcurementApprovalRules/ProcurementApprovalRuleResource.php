<?php

namespace App\Filament\Resources\ProcurementApprovalRules;

use App\Filament\Resources\ProcurementApprovalRules\Pages\CreateProcurementApprovalRule;
use App\Filament\Resources\ProcurementApprovalRules\Pages\EditProcurementApprovalRule;
use App\Filament\Resources\ProcurementApprovalRules\Pages\ListProcurementApprovalRules;
use App\Filament\Resources\ProcurementApprovalRules\Pages\ViewProcurementApprovalRule;
use App\Filament\Resources\ProcurementApprovalRules\Schemas\ProcurementApprovalRuleForm;
use App\Filament\Resources\ProcurementApprovalRules\Schemas\ProcurementApprovalRuleInfolist;
use App\Filament\Resources\ProcurementApprovalRules\Tables\ProcurementApprovalRulesTable;
use App\Models\ProcurementApprovalRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProcurementApprovalRuleResource extends Resource
{
    protected static ?string $model = ProcurementApprovalRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $tenantRelationshipName = 'procurementApprovalRules';

    protected static \UnitEnum|string|null $navigationGroup = 'Approvals';

    protected static ?string $navigationLabel = 'Procurement Approval Rules';

    public static function form(Schema $schema): Schema
    {
        return ProcurementApprovalRuleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProcurementApprovalRuleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProcurementApprovalRulesTable::configure($table);
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
            'index' => ListProcurementApprovalRules::route('/'),
            'create' => CreateProcurementApprovalRule::route('/create'),
            'view' => ViewProcurementApprovalRule::route('/{record}'),
            'edit' => EditProcurementApprovalRule::route('/{record}/edit'),
        ];
    }
}
