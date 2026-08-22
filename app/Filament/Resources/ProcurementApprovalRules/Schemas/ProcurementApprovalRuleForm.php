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
            Section::make('Procurement Approval Step Configuration')
                ->description('Matching active steps run in step-number order. If no rule matches, one default Finance Approval step is used.')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Select::make('document_type')
                        ->label('Document Type')
                        ->options(ProcurementDocumentType::class)
                        ->required(),
                    TextInput::make('step_number')
                        ->label('Step Sequence Number')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('name')
                        ->label('Step Name / Designation')
                        ->maxLength(255)
                        ->required(),
                    Select::make('permission_name')
                        ->label('Required Approval Permission')
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
                        ->required()
                        ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                    TextInput::make('minimum_amount')
                        ->label('Minimum Threshold (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0),
                    TextInput::make('maximum_amount')
                        ->label('Maximum Threshold (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0),
                    Toggle::make('is_active')
                        ->label('Is Active')
                        ->default(true)
                        ->required(),
                ]),
        ]);
    }
}
