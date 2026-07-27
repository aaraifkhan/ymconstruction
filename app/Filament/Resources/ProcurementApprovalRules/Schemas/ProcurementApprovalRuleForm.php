<?php

namespace App\Filament\Resources\ProcurementApprovalRules\Schemas;

use App\Enums\ProcurementDocumentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

class ProcurementApprovalRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Approval step')
                ->description('Matching active steps run in step-number order. If no rule matches, one default Finance Approval step is used.')
                ->columns(2)
                ->schema([
                    Select::make('document_type')->options(ProcurementDocumentType::class)->required(),
                    TextInput::make('step_number')->integer()->minValue(1)->required(),
                    TextInput::make('name')->maxLength(255)->required(),
                    Select::make('permission_name')
                        ->options(fn (): array => Permission::query()
                            ->where(fn ($query) => $query
                                ->whereIn('name', [
                                    'Approve:PurchaseRequisition',
                                    'Approve:PurchaseOrder',
                                ])
                                ->orWhere('name', 'like', '%:Procurement'))
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('minimum_amount')->numeric()->minValue(0),
                    TextInput::make('maximum_amount')->numeric()->minValue(0),
                    Toggle::make('is_active')->default(true)->required(),
                ]),
        ]);
    }
}
