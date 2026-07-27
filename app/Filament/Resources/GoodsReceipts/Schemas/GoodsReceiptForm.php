<?php

namespace App\Filament\Resources\GoodsReceipts\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\UnitOfMeasure;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class GoodsReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vendor delivery')->columns(3)->schema([
                Select::make('purchase_order_id')
                    ->label('Issued purchase order')
                    ->options(fn (): array => PurchaseOrder::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->whereIn('status', [PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived])
                        ->latest()->get()
                        ->mapWithKeys(fn (PurchaseOrder $order): array => [
                            $order->getKey() => $order->purchase_order_number ?? 'Issued PO #'.$order->getKey(),
                        ])->all())
                    ->live()->searchable()->required(),
                Select::make('vendor_id')
                    ->options(fn (): array => Party::query()->whereBelongsTo(Filament::getTenant())
                        ->active()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()->required(),
                DatePicker::make('delivery_date')->default(today())->required(),
                Select::make('project_id')
                    ->options(fn (): array => Project::query()->whereBelongsTo(Filament::getTenant())
                        ->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()->required(),
                Select::make('project_site_id')
                    ->label('Receiving site / store')
                    ->options(fn (): array => ProjectSite::query()->whereBelongsTo(Filament::getTenant())
                        ->active()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()->required(),
                TextInput::make('delivery_reference')->maxLength(255),
                Textarea::make('receiving_notes')->columnSpanFull(),
            ]),
            Section::make('Delivered materials')->schema([
                Repeater::make('lines')
                    ->relationship()
                    ->orderColumn('line_number')
                    ->minItems(1)
                    ->defaultItems(1)
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                        ...$data,
                        'company_id' => Filament::getTenant()->getKey(),
                    ])
                    ->schema([
                        Select::make('purchase_order_line_id')
                            ->label('PO line')
                            ->options(fn (Get $get): array => PurchaseOrderLine::query()
                                ->where('purchase_order_id', $get('../../purchase_order_id'))
                                ->orderBy('line_number')->get()
                                ->filter(fn (PurchaseOrderLine $line): bool => bccomp($line->availableToReceive(), '0', 4) === 1)
                                ->mapWithKeys(fn (PurchaseOrderLine $line): array => [
                                    $line->getKey() => "{$line->line_number}. {$line->item_name_snapshot} ({$line->availableToReceive()} available)",
                                ])->all())
                            ->searchable()->required(),
                        Select::make('item_id')
                            ->options(fn (): array => Item::query()->whereBelongsTo(Filament::getTenant())
                                ->active()->where('track_inventory', true)->orderBy('name')
                                ->get()->mapWithKeys(fn (Item $item): array => [
                                    $item->getKey() => "{$item->code} — {$item->name}",
                                ])->all())
                            ->searchable()->required(),
                        Select::make('unit_of_measure_id')
                            ->label('UOM')
                            ->options(fn (): array => UnitOfMeasure::query()->whereBelongsTo(Filament::getTenant())
                                ->active()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->required(),
                        TextInput::make('received_quantity')->numeric()->minValue(0.0001)->required(),
                    ])->columns(4)->columnSpanFull(),
            ]),
        ]);
    }
}
