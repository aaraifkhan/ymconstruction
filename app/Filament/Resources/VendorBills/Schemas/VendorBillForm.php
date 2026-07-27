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
        return $schema->components([
            Section::make('Vendor document')->columns(3)->schema([
                Select::make('type')->options(VendorBillType::class)->default(VendorBillType::Invoice)->live()->required(),
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
                    ->searchable()->required(fn (Get $get): bool => $get('type') === VendorBillType::Invoice->value),
                Select::make('original_vendor_bill_id')
                    ->label('Original posted bill')
                    ->options(fn (): array => VendorBill::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->where('type', VendorBillType::Invoice)
                        ->where('status', VendorBillStatus::Posted)
                        ->latest()->get()->mapWithKeys(fn (VendorBill $bill): array => [
                            $bill->getKey() => $bill->vendor_bill_number ?? 'Vendor Bill #'.$bill->getKey(),
                        ])->all())
                    ->searchable()->visible(fn (Get $get): bool => $get('type') === VendorBillType::CreditNote->value)
                    ->required(fn (Get $get): bool => $get('type') === VendorBillType::CreditNote->value),
                Select::make('vendor_id')
                    ->options(fn (): array => Party::query()->whereBelongsTo(Filament::getTenant())
                        ->active()->get()->filter(fn (Party $party): bool => $party->hasRole(PartyRole::Vendor))
                        ->sortBy('name')->pluck('name', 'id')->all())
                    ->searchable()->required(),
                TextInput::make('vendor_invoice_number')->label('Vendor invoice / credit-note no.')->required()->maxLength(100),
                DatePicker::make('invoice_date')->default(today())->required(),
                DatePicker::make('due_date')->default(today()->addDays(30))->required(),
                Select::make('project_id')->options(fn (): array => Project::query()
                    ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                Select::make('project_site_id')->options(fn (): array => ProjectSite::query()
                    ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                TextInput::make('currency_code')->default('PKR')->disabled()->dehydrated(),
                Textarea::make('notes')->columnSpanFull(),
            ]),
            Section::make('Invoice lines')
                ->description('Stock quantities are allocated FIFO to handed-over accepted GRNs at submission.')
                ->schema([
                    Repeater::make('lines')->relationship()->orderColumn('line_number')
                        ->minItems(1)->defaultItems(1)
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                            ...$data,
                            'company_id' => Filament::getTenant()->getKey(),
                            'project_id' => $data['project_id'] ?? null,
                            'project_site_id' => $data['project_site_id'] ?? null,
                        ])
                        ->schema([
                            Select::make('purchase_order_line_id')->label('PO line')
                                ->options(fn (Get $get): array => PurchaseOrderLine::query()
                                    ->where('purchase_order_id', $get('../../purchase_order_id'))
                                    ->orderBy('line_number')->get()
                                    ->mapWithKeys(fn (PurchaseOrderLine $line): array => [
                                        $line->getKey() => "{$line->line_number}. {$line->item_name_snapshot}",
                                    ])->all())->searchable(),
                            Select::make('original_vendor_bill_line_id')->label('Original bill line')
                                ->relationship('originalVendorBillLine', 'item_name_snapshot')->searchable(),
                            Select::make('item_id')->options(fn (): array => Item::query()
                                ->whereBelongsTo(Filament::getTenant())->active()->orderBy('name')
                                ->get()->mapWithKeys(fn (Item $item): array => [
                                    $item->getKey() => "{$item->code} — {$item->name}",
                                ])->all())->searchable()->required(),
                            Select::make('unit_of_measure_id')->label('UOM')->options(fn (): array => UnitOfMeasure::query()
                                ->whereBelongsTo(Filament::getTenant())->active()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()->required(),
                            TextInput::make('item_name_snapshot')->label('Line description')->required(),
                            TextInput::make('quantity')->numeric()->minValue(0.0001)->required(),
                            TextInput::make('unit_rate')->numeric()->minValue(0)->required(),
                            Select::make('tax_code_id')->options(fn (): array => TaxCode::query()
                                ->whereBelongsTo(Filament::getTenant())->where('is_active', true)
                                ->orderBy('code')->pluck('name', 'id')->all())->searchable(),
                            Select::make('clearing_account_id')->label('Direct cost / expense account')
                                ->options(fn (): array => self::postingAccountOptions())->searchable(),
                            Select::make('variance_account_id')->label('Price variance account')
                                ->options(fn (): array => self::postingAccountOptions())->searchable(),
                            Select::make('project_id')->options(fn (): array => Project::query()
                                ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                            Select::make('project_site_id')->options(fn (): array => ProjectSite::query()
                                ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                            Textarea::make('description')->columnSpanFull(),
                        ])->columns(4)->columnSpanFull(),
                ]),
            Section::make('WHT, retention, advances & deductions')->schema([
                Repeater::make('deductions')->relationship()->defaultItems(0)
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                        ...$data,
                        'company_id' => Filament::getTenant()->getKey(),
                    ])
                    ->schema([
                        Select::make('type')->options(VendorBillDeductionType::class)->required(),
                        TextInput::make('description')->required(),
                        Select::make('tax_code_id')->label('WHT code')->options(fn (): array => TaxCode::query()
                            ->whereBelongsTo(Filament::getTenant())->where('is_active', true)
                            ->orderBy('code')->pluck('name', 'id')->all())->searchable(),
                        TextInput::make('amount')->numeric()->minValue(0.0001)->required(),
                        Select::make('account_id')->label('Explicit account (Other)')
                            ->options(fn (): array => self::postingAccountOptions())->searchable(),
                    ])->columns(5),
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
