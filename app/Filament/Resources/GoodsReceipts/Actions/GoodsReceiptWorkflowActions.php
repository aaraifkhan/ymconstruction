<?php

namespace App\Filament\Resources\GoodsReceipts\Actions;

use App\Actions\Inventory\HandoverGoodsReceiptToAccountsAction;
use App\Actions\Inventory\InspectGoodsReceiptAction;
use App\Actions\Inventory\ReceiveGoodsAction;
use App\Actions\Inventory\RecordRejectedGoodsReturnAction;
use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class GoodsReceiptWorkflowActions
{
    public static function receive(): Action
    {
        return Action::make('receive')->authorize('receive')->color('warning')
            ->visible(fn (GoodsReceipt $record): bool => $record->status === GoodsReceiptStatus::Draft)
            ->requiresConfirmation()
            ->action(fn (GoodsReceipt $record) => app(ReceiveGoodsAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function inspect(): Action
    {
        return Action::make('inspect')->authorize('inspect')->color('primary')
            ->visible(fn (GoodsReceipt $record): bool => $record->status === GoodsReceiptStatus::Received)
            ->schema([
                Repeater::make('decisions')
                    ->default(fn (GoodsReceipt $record): array => $record->lines->map(
                        fn (GoodsReceiptLine $line): array => [
                            'line_id' => $line->getKey(),
                            'item' => $line->item_name_snapshot,
                            'received_quantity' => $line->received_quantity,
                            'accepted_quantity' => $line->received_quantity,
                            'rejected_quantity' => '0.0000',
                        ],
                    )->all())
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->schema([
                        Hidden::make('line_id'),
                        TextInput::make('item')->disabled()->dehydrated(false),
                        TextInput::make('received_quantity')->disabled()->dehydrated(false),
                        TextInput::make('accepted_quantity')->numeric()->minValue(0)->required(),
                        TextInput::make('rejected_quantity')->numeric()->minValue(0)->required(),
                        Textarea::make('inspection_notes'),
                        Textarea::make('rejection_reason'),
                    ])->columns(3),
                Textarea::make('inspection_notes')->label('Overall inspection notes'),
            ])
            ->action(function (GoodsReceipt $record, array $data): void {
                $decisions = collect($data['decisions'])->mapWithKeys(
                    fn (array $decision): array => [
                        $decision['line_id'] => [
                            'accepted_quantity' => (string) $decision['accepted_quantity'],
                            'rejected_quantity' => (string) $decision['rejected_quantity'],
                            'inspection_notes' => $decision['inspection_notes'] ?? null,
                            'rejection_reason' => $decision['rejection_reason'] ?? null,
                        ],
                    ],
                )->all();

                app(InspectGoodsReceiptAction::class)->handle(
                    $record,
                    Filament::auth()->user(),
                    $decisions,
                    $data['inspection_notes'] ?? null,
                );
            });
    }

    public static function handover(): Action
    {
        return Action::make('handover')->authorize('handover')->color('success')
            ->label('Handover to Accounts')
            ->visible(fn (GoodsReceipt $record): bool => $record->status === GoodsReceiptStatus::Inspected)
            ->requiresConfirmation()
            ->action(fn (GoodsReceipt $record) => app(HandoverGoodsReceiptToAccountsAction::class)
                ->handle($record, Filament::auth()->user()));
    }

    public static function returnRejected(): Action
    {
        return Action::make('returnRejected')->color('danger')
            ->label('Return rejected material')
            ->visible(fn (GoodsReceipt $record): bool => in_array($record->status, [
                GoodsReceiptStatus::Inspected,
                GoodsReceiptStatus::HandedOver,
            ], true) && $record->lines->contains(
                fn (GoodsReceiptLine $line): bool => bccomp($line->availableRejectedToReturn(), '0', 4) === 1,
            ))
            ->schema([
                Select::make('line_id')
                    ->label('Rejected line')
                    ->options(fn (GoodsReceipt $record): array => $record->lines
                        ->filter(fn (GoodsReceiptLine $line): bool => bccomp($line->availableRejectedToReturn(), '0', 4) === 1)
                        ->mapWithKeys(fn (GoodsReceiptLine $line): array => [
                            $line->getKey() => "{$line->item_name_snapshot} — {$line->availableRejectedToReturn()} available",
                        ])->all())
                    ->required(),
                TextInput::make('quantity')->numeric()->minValue(0.0001)->required(),
            ])
            ->action(function (GoodsReceipt $record, array $data): void {
                $line = $record->lines()->findOrFail($data['line_id']);
                Filament::auth()->user()->can('returnRejected', $line);
                app(RecordRejectedGoodsReturnAction::class)->handle(
                    $line,
                    (string) $data['quantity'],
                    Filament::auth()->user(),
                );
            });
    }
}
