<?php

namespace App\Filament\Resources\InventoryTransactions\Schemas;

use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryTransactionType;
use App\Models\Account;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\Project;
use App\Models\ProjectSite;
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

class InventoryTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Inventory transaction')->columns(3)->schema([
                Select::make('type')
                    ->options(InventoryTransactionType::class)
                    ->live()->required(),
                DatePicker::make('transaction_date')->default(today())->required(),
                TextInput::make('reference')->maxLength(255),
                Select::make('source_site_id')
                    ->label('Source site / store')
                    ->options(fn (): array => ProjectSite::query()->whereBelongsTo(Filament::getTenant())
                        ->active()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                Select::make('destination_site_id')
                    ->label('Destination site / store')
                    ->options(fn (): array => ProjectSite::query()->whereBelongsTo(Filament::getTenant())
                        ->active()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                Select::make('project_id')
                    ->options(fn (): array => Project::query()->whereBelongsTo(Filament::getTenant())
                        ->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                Select::make('goods_receipt_id')
                    ->label('Goods Receipt for vendor return')
                    ->options(fn (): array => GoodsReceipt::query()->whereBelongsTo(Filament::getTenant())
                        ->where('status', GoodsReceiptStatus::HandedOver)
                        ->latest()->pluck('goods_receipt_number', 'id')->all())
                    ->searchable(),
                Textarea::make('reason')->required()->columnSpanFull(),
            ]),
            Section::make('Materials')->schema([
                Repeater::make('lines')
                    ->relationship()
                    ->orderColumn('line_number')
                    ->minItems(1)->defaultItems(1)
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                        ...$data,
                        'company_id' => Filament::getTenant()->getKey(),
                    ])
                    ->schema([
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
                        Select::make('goods_receipt_line_id')
                            ->label('Accepted GRN line')
                            ->options(fn (Get $get): array => GoodsReceiptLine::query()
                                ->where('goods_receipt_id', $get('../../goods_receipt_id'))
                                ->orderBy('line_number')->get()
                                ->filter(fn (GoodsReceiptLine $line): bool => bccomp($line->availableAcceptedToReturn(), '0', 4) === 1)
                                ->mapWithKeys(fn (GoodsReceiptLine $line): array => [
                                    $line->getKey() => "{$line->item_name_snapshot} — {$line->availableAcceptedToReturn()} available",
                                ])->all())
                            ->searchable(),
                        Select::make('offset_account_id')
                            ->label('Cost / adjustment account')
                            ->options(fn (): array => Account::query()->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true)->whereDoesntHave('children')
                                ->orderBy('code')->get()->mapWithKeys(fn (Account $account): array => [
                                    $account->getKey() => "{$account->code} — {$account->name}",
                                ])->all())
                            ->searchable(),
                        TextInput::make('quantity')->numeric()->minValue(0.0001)->required(),
                        TextInput::make('unit_cost_snapshot')
                            ->label('Inbound unit cost')
                            ->numeric()->minValue(0)
                            ->helperText('Used for adjustment increases and first Project returns; outbound cost is calculated by weighted average.'),
                        Textarea::make('notes')->columnSpanFull(),
                    ])->columns(3)->columnSpanFull(),
            ]),
        ]);
    }
}
