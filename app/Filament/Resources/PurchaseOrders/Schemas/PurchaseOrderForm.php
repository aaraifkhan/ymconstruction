<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\PartyRole;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionLine;
use App\Models\TaxCode;
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

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Purchase order')
                ->columns(3)
                ->schema([
                    Select::make('purchase_requisition_id')
                        ->label('Approved requisition')
                        ->options(fn (): array => PurchaseRequisition::query()
                            ->whereBelongsTo(Filament::getTenant())
                            ->whereIn('status', [
                                PurchaseRequisitionStatus::Approved,
                                PurchaseRequisitionStatus::PartiallyOrdered,
                            ])
                            ->latest()
                            ->pluck('requisition_number', 'id')
                            ->all())
                        ->searchable(),
                    Select::make('vendor_id')
                        ->options(fn (): array => Party::query()
                            ->whereBelongsTo(Filament::getTenant())
                            ->active()
                            ->orderBy('name')
                            ->get()
                            ->filter(fn (Party $party): bool => $party->hasRole(PartyRole::Vendor))
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                    DatePicker::make('order_date')->default(today())->required(),
                    Select::make('project_id')->relationship('project', 'name')->searchable()->preload()->required(),
                    Select::make('project_site_id')->relationship('projectSite', 'name')->searchable()->preload()->required(),
                    TextInput::make('currency_code')->default('PKR')->length(3)->disabled()->dehydrated(),
                    TextInput::make('payment_terms_days')->integer()->minValue(0)->default(0)->required(),
                    Textarea::make('payment_terms')->columnSpan(2),
                    Textarea::make('notes')->columnSpanFull(),
                ]),
            Section::make('Ordered materials and services')
                ->schema([
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
                            Select::make('purchase_requisition_line_id')
                                ->label('Requisition line')
                                ->options(fn (Get $get): array => PurchaseRequisitionLine::query()
                                    ->where('purchase_requisition_id', $get('../../purchase_requisition_id'))
                                    ->orderBy('line_number')
                                    ->get()
                                    ->mapWithKeys(fn (PurchaseRequisitionLine $line): array => [
                                        $line->getKey() => "{$line->line_number}. {$line->item_name_snapshot} (remaining "
                                            .bcsub((string) $line->quantity, (string) $line->ordered_quantity, 4).')',
                                    ])->all())
                                ->searchable(),
                            Select::make('item_id')
                                ->options(fn (): array => Item::query()->whereBelongsTo(Filament::getTenant())->active()
                                    ->orderBy('name')->get()->mapWithKeys(
                                        fn (Item $item): array => [$item->getKey() => "{$item->code} — {$item->name}"],
                                    )->all())
                                ->searchable()
                                ->required(),
                            Select::make('unit_of_measure_id')
                                ->label('UOM')
                                ->options(fn (): array => UnitOfMeasure::query()->whereBelongsTo(Filament::getTenant())
                                    ->active()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required(),
                            Select::make('tax_code_id')
                                ->label('Tax code')
                                ->options(fn (): array => TaxCode::query()->whereBelongsTo(Filament::getTenant())
                                    ->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable(),
                            TextInput::make('quantity')->numeric()->minValue(0.0001)->required(),
                            TextInput::make('unit_rate')->numeric()->minValue(0)->required(),
                            Textarea::make('specification')->rows(2)->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
