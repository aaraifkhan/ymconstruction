<?php

namespace App\Filament\Resources\VendorBills\Schemas;

use App\Enums\PartyRole;
use App\Enums\PurchaseOrderStatus;
use App\Enums\VendorBillDeductionType;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\Account;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\TaxCode;
use App\Models\UnitOfMeasure;
use App\Models\VendorBill;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class VendorBillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Vendor Document Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Select::make('type')
                            ->label('Bill Type')
                            ->options(VendorBillType::class)
                            ->default(VendorBillType::Invoice)
                            ->live()
                            ->required(),
                        Select::make('purchase_order_id')
                            ->label('Issued Purchase Order')
                            ->options(fn (): array => PurchaseOrder::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->whereIn('status', [
                                    PurchaseOrderStatus::Ordered,
                                    PurchaseOrderStatus::PartiallyReceived,
                                    PurchaseOrderStatus::Received,
                                ])->latest()->get()->mapWithKeys(fn (PurchaseOrder $order): array => [
                                    $order->getKey() => $order->purchase_order_number ?? 'Issued PO #'.$order->getKey(),
                                ])->all())
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('type') === VendorBillType::Invoice->value),
                        Select::make('original_vendor_bill_id')
                            ->label('Original Posted Bill')
                            ->options(fn (): array => VendorBill::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->where('type', VendorBillType::Invoice)
                                ->where('status', VendorBillStatus::Posted)
                                ->latest()->get()->mapWithKeys(fn (VendorBill $bill): array => [
                                    $bill->getKey() => $bill->vendor_bill_number ?? 'Vendor Bill #'.$bill->getKey(),
                                ])->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('type') === VendorBillType::CreditNote->value)
                            ->required(fn (Get $get): bool => $get('type') === VendorBillType::CreditNote->value),
                        Select::make('vendor_id')
                            ->label('Vendor / Supplier')
                            ->options(fn (): array => Party::query()->whereBelongsTo(Filament::getTenant())
                                ->active()->get()->filter(fn (Party $party): bool => $party->hasRole(PartyRole::Vendor))
                                ->sortBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('vendor_invoice_number')
                            ->label('Vendor Invoice / Bill No.')
                            ->required()
                            ->maxLength(100),
                        DatePicker::make('invoice_date')
                            ->label('Invoice Date')
                            ->default(today())
                            ->required(),
                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->default(today()->addDays(30))
                            ->required(),
                        Select::make('project_id')
                            ->label('Project')
                            ->options(fn (): array => Project::query()
                                ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable(),
                        Select::make('project_site_id')
                            ->label('Project Site')
                            ->options(fn (): array => ProjectSite::query()
                                ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable(),
                        TextInput::make('currency_code')
                            ->label('Currency')
                            ->default('PKR')
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('notes')
                            ->label('Bill Notes / Remarks')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Invoice Lines')
                    ->columnSpanFull()
                    ->description('Stock quantities are allocated FIFO to handed-over accepted GRNs at submission.')
                    ->schema([
                        Repeater::make('lines')
                            ->relationship()
                            ->orderColumn('line_number')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                                ...$data,
                                'company_id' => Filament::getTenant()->getKey(),
                                'project_id' => $data['project_id'] ?? null,
                                'project_site_id' => $data['project_site_id'] ?? null,
                            ])
                            ->schema([
                                Select::make('item_id')
                                    ->label('Item / Material')
                                    ->options(fn (): array => Item::query()
                                        ->whereBelongsTo(Filament::getTenant())->active()->orderBy('name')
                                        ->get()->mapWithKeys(fn (Item $item): array => [
                                            $item->getKey() => "{$item->code} — {$item->name}",
                                        ])->all())
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                TextInput::make('item_name_snapshot')
                                    ->label('Line Description')
                                    ->required()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                Select::make('purchase_order_line_id')
                                    ->label('PO Line')
                                    ->options(fn (Get $get): array => PurchaseOrderLine::query()
                                        ->where('purchase_order_id', $get('../../purchase_order_id'))
                                        ->orderBy('line_number')->get()
                                        ->mapWithKeys(fn (PurchaseOrderLine $line): array => [
                                            $line->getKey() => "{$line->line_number}. {$line->item_name_snapshot}",
                                        ])->all())
                                    ->searchable()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                Select::make('unit_of_measure_id')
                                    ->label('UOM')
                                    ->options(fn (): array => UnitOfMeasure::query()
                                        ->whereBelongsTo(Filament::getTenant())->active()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->required(),
                                Select::make('tax_code_id')
                                    ->label('Tax Code')
                                    ->options(fn (): array => TaxCode::query()
                                        ->whereBelongsTo(Filament::getTenant())->where('is_active', true)
                                        ->orderBy('code')->pluck('name', 'id')->all())
                                    ->searchable(),
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->minValue(0.0001)
                                    ->required(),
                                TextInput::make('unit_rate')
                                    ->label('Unit Rate (PKR)')
                                    ->numeric()
                                    ->prefix('PKR')
                                    ->minValue(0)
                                    ->required(),
                                Select::make('clearing_account_id')
                                    ->label('Direct Cost / Expense Account')
                                    ->options(fn (): array => self::postingAccountOptions())
                                    ->searchable()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                Select::make('variance_account_id')
                                    ->label('Price Variance Account')
                                    ->options(fn (): array => self::postingAccountOptions())
                                    ->searchable()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                Select::make('project_id')
                                    ->label('Project')
                                    ->options(fn (): array => Project::query()
                                        ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable(),
                                Select::make('project_site_id')
                                    ->label('Project Site')
                                    ->options(fn (): array => ProjectSite::query()
                                        ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable(),
                                Textarea::make('description')
                                    ->label('Line Remarks')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                            ->columnSpanFull(),
                    ]),
                Section::make('WHT, Retention, Advances & Deductions')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('deductions')
                            ->relationship()
                            ->defaultItems(0)
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                                ...$data,
                                'company_id' => Filament::getTenant()->getKey(),
                            ])
                            ->schema([
                                Select::make('type')
                                    ->label('Deduction Type')
                                    ->options(VendorBillDeductionType::class)
                                    ->required(),
                                TextInput::make('amount')
                                    ->label('Amount (PKR)')
                                    ->numeric()
                                    ->prefix('PKR')
                                    ->minValue(0.0001)
                                    ->required(),
                                TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                Select::make('tax_code_id')
                                    ->label('WHT Code')
                                    ->options(fn (): array => TaxCode::query()
                                        ->whereBelongsTo(Filament::getTenant())->where('is_active', true)
                                        ->orderBy('code')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                                Select::make('account_id')
                                    ->label('Explicit Account (Other)')
                                    ->options(fn (): array => self::postingAccountOptions())
                                    ->searchable()
                                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                            ])
                            ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** @return array<int, string> */
    private static function postingAccountOptions(): array
    {
        return Account::query()->whereBelongsTo(Filament::getTenant())
            ->where('is_active', true)->where('allows_manual_posting', true)
            ->orderBy('code')->get()->mapWithKeys(fn (Account $account): array => [
                $account->getKey() => "{$account->code} — {$account->name}",
            ])->all();
    }
}
