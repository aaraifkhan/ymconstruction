<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchase order')->columns(3)->schema([
                    TextEntry::make('company.name')
                        ->label('Company'),
                    TextEntry::make('purchase_requisition_id')
                        ->numeric()
                        ->placeholder('-'),
                    TextEntry::make('vendor.name')
                        ->label('Vendor'),
                    TextEntry::make('project.name')
                        ->label('Project'),
                    TextEntry::make('projectSite.name')
                        ->label('Project site'),
                    TextEntry::make('purchase_order_number')
                        ->placeholder('-'),
                    TextEntry::make('order_date')
                        ->date(),
                    TextEntry::make('status')
                        ->badge(),
                    TextEntry::make('approval_round')
                        ->numeric(),
                    TextEntry::make('currency_code'),
                    TextEntry::make('payment_terms_days')
                        ->numeric(),
                    TextEntry::make('payment_terms')
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('notes')
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('subtotal')
                        ->numeric(),
                    TextEntry::make('tax_total')
                        ->numeric(),
                    TextEntry::make('grand_total')
                        ->numeric(),
                    TextEntry::make('approved_snapshot')
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('approved_snapshot_hash')
                        ->placeholder('-'),
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
                    TextEntry::make('orderedBy.name')
                        ->label('Ordered by')
                        ->placeholder('-'),
                    TextEntry::make('ordered_at')
                        ->dateTime()
                        ->placeholder('-'),
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
                        TextEntry::make('unit_rate')->numeric(decimalPlaces: 4),
                        TextEntry::make('tax_code_snapshot')->label('Tax')->placeholder('-'),
                        TextEntry::make('tax_amount')->numeric(decimalPlaces: 4),
                        TextEntry::make('line_total')->numeric(decimalPlaces: 4),
                        TextEntry::make('received_quantity')->numeric(decimalPlaces: 4),
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
