<?php

namespace App\Filament\Resources\PurchaseRequisitions\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseRequisitionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Requisition')->columns(3)->schema([
                    TextEntry::make('company.name')
                        ->label('Company'),
                    TextEntry::make('project.name')
                        ->label('Project'),
                    TextEntry::make('projectSite.name')
                        ->label('Project site'),
                    TextEntry::make('requisition_number')
                        ->placeholder('-'),
                    TextEntry::make('required_date')
                        ->date(),
                    TextEntry::make('status')
                        ->badge(),
                    TextEntry::make('approval_round')
                        ->numeric(),
                    TextEntry::make('currency_code'),
                    TextEntry::make('reason')
                        ->columnSpanFull(),
                    TextEntry::make('estimated_total')
                        ->numeric(),
                    TextEntry::make('budget_check_status'),
                    TextEntry::make('budget_check_snapshot')
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('preparedBy.name')
                        ->label('Prepared by'),
                    TextEntry::make('submittedBy.name')
                        ->label('Submitted by')
                        ->placeholder('-'),
                    TextEntry::make('submitted_at')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('approvedBy.name')
                        ->label('Approved by')
                        ->placeholder('-'),
                    TextEntry::make('approved_at')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('rejectedBy.name')
                        ->label('Rejected by')
                        ->placeholder('-'),
                    TextEntry::make('rejected_at')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('rejection_reason')
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('cancelledBy.name')
                        ->label('Cancelled by')
                        ->placeholder('-'),
                    TextEntry::make('cancelled_at')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('cancellation_reason')
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('created_at')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('updated_at')
                        ->dateTime()
                        ->placeholder('-'),
                ]),
                Section::make('Lines')->schema([
                    RepeatableEntry::make('lines')->schema([
                        TextEntry::make('line_number')->label('#'),
                        TextEntry::make('item_name_snapshot')->label('Item'),
                        TextEntry::make('uom_snapshot')->label('UOM'),
                        TextEntry::make('quantity')->numeric(decimalPlaces: 4),
                        TextEntry::make('estimated_rate')->numeric(decimalPlaces: 4),
                        TextEntry::make('estimated_amount')->numeric(decimalPlaces: 4),
                        TextEntry::make('ordered_quantity')->numeric(decimalPlaces: 4),
                        TextEntry::make('specification')->placeholder('-'),
                    ])->columns(4),
                ]),
                Section::make('Approval evidence')->schema([
                    RepeatableEntry::make('approvalSteps')->schema([
                        TextEntry::make('approval_round')->label('Round'),
                        TextEntry::make('step_number')->label('Step'),
                        TextEntry::make('name'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('decidedBy.name')->label('Decided by')->placeholder('-'),
                        TextEntry::make('decided_at')->dateTime()->placeholder('-'),
                        TextEntry::make('decision_reason')->placeholder('-'),
                    ])->columns(4),
                ]),
            ]);
    }
}
