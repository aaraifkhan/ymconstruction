<?php

namespace App\Filament\Resources\CustomerInvoices\Schemas;

use App\Enums\AccountType;
use App\Enums\CustomerInvoiceAdjustmentType;
use App\Enums\CustomerInvoiceCategory;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\PartyRole;
use App\Enums\TaxCodeType;
use App\Models\Account;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceLine;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectSite;
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

class CustomerInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer Document Details')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->schema([
                    Select::make('type')
                        ->label('Invoice Type')
                        ->options(CustomerInvoiceType::class)
                        ->default(CustomerInvoiceType::Invoice)
                        ->live()
                        ->required(),
                    Select::make('category')
                        ->label('Invoice Category')
                        ->options(CustomerInvoiceCategory::class)
                        ->default(CustomerInvoiceCategory::ServiceInvoice)
                        ->live()
                        ->required(),
                    Select::make('original_customer_invoice_id')
                        ->label('Original Posted Invoice')
                        ->options(fn (): array => CustomerInvoice::query()->whereBelongsTo(Filament::getTenant())
                            ->where('type', CustomerInvoiceType::Invoice)->where('status', CustomerInvoiceStatus::Posted)
                            ->latest()->pluck('invoice_number', 'id')->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('type') === CustomerInvoiceType::CreditNote->value)
                        ->required(fn (Get $get): bool => $get('type') === CustomerInvoiceType::CreditNote->value),
                    Select::make('customer_id')
                        ->label('Customer / Client')
                        ->options(fn (): array => Party::query()->whereBelongsTo(Filament::getTenant())
                            ->active()->get()->filter(fn (Party $party): bool => $party->hasRole(PartyRole::Customer))
                            ->sortBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('customer_reference')
                        ->label('Customer Ref / PO No.')
                        ->maxLength(255),
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
                        ->options(fn (): array => Project::query()->whereBelongsTo(Filament::getTenant())
                            ->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                    Select::make('project_site_id')
                        ->label('Project Site')
                        ->options(fn (): array => ProjectSite::query()->whereBelongsTo(Filament::getTenant())
                            ->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    TextInput::make('certificate_number')
                        ->label('IPC / Interim Certificate No.')
                        ->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                    DatePicker::make('certificate_date')
                        ->label('Certificate Date')
                        ->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                    TextInput::make('work_value')
                        ->label('Work Done Value (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                    TextInput::make('variation_amount')
                        ->label('Variation Amount (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                    TextInput::make('currency_code')
                        ->label('Currency')
                        ->default('PKR')
                        ->disabled()
                        ->dehydrated(),
                    Textarea::make('description')
                        ->label('Invoice Narration / Particulars')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            Section::make('Revenue Lines')
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
                            Select::make('original_customer_invoice_line_id')
                                ->label('Original Invoice Line')
                                ->options(fn (Get $get): array => CustomerInvoiceLine::query()
                                    ->where('customer_invoice_id', $get('../../../original_customer_invoice_id'))
                                    ->orderBy('line_number')->pluck('item_name_snapshot', 'id')->all())
                                ->searchable()
                                ->visible(fn (Get $get): bool => $get('../../../type') === CustomerInvoiceType::CreditNote->value)
                                ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                            Select::make('item_id')
                                ->label('Item / Service')
                                ->options(fn (): array => Item::query()->whereBelongsTo(Filament::getTenant())
                                    ->active()->orderBy('name')->get()->mapWithKeys(fn (Item $item): array => [
                                        $item->getKey() => "{$item->code} — {$item->name}",
                                    ])->all())
                                ->searchable()
                                ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                            TextInput::make('item_name_snapshot')
                                ->label('Description / Particulars')
                                ->required()
                                ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                            Select::make('unit_of_measure_id')
                                ->label('UOM')
                                ->options(fn (): array => UnitOfMeasure::query()
                                    ->whereBelongsTo(Filament::getTenant())->active()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable(),
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->minValue(0.0001)
                                ->default(1)
                                ->required(),
                            TextInput::make('unit_rate')
                                ->label('Unit Rate (PKR)')
                                ->numeric()
                                ->prefix('PKR')
                                ->minValue(0)
                                ->required(),
                            Select::make('revenue_account_id')
                                ->label('Revenue Account')
                                ->options(fn (): array => self::accountOptions(AccountType::Revenue))
                                ->searchable()
                                ->required()
                                ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                            Select::make('tax_code_id')
                                ->label('Sales Tax Code')
                                ->options(fn (): array => TaxCode::query()
                                    ->whereBelongsTo(Filament::getTenant())->where('type', TaxCodeType::SalesTax)
                                    ->where('is_active', true)->orderBy('code')->pluck('name', 'id')->all())
                                ->searchable()
                                ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                            Select::make('inventory_site_id')
                                ->label('Inventory Site')
                                ->options(fn (): array => ProjectSite::query()
                                    ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->visible(fn (Get $get): bool => $get('../../../category') === CustomerInvoiceCategory::TradingSale->value),
                            Select::make('cogs_account_id')
                                ->label('COGS Account')
                                ->options(fn (): array => self::accountOptions(AccountType::Expense))
                                ->searchable()
                                ->visible(fn (Get $get): bool => $get('../../../category') === CustomerInvoiceCategory::TradingSale->value),
                            Textarea::make('description')
                                ->label('Line Remarks')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                        ->columnSpanFull(),
                ]),
            Section::make('Running-Bill Deductions')
                ->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value)
                ->schema([
                    Repeater::make('adjustments')
                        ->relationship()
                        ->defaultItems(0)
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                            ...$data,
                            'company_id' => Filament::getTenant()->getKey(),
                        ])
                        ->schema([
                            Select::make('type')
                                ->label('Adjustment Type')
                                ->options(CustomerInvoiceAdjustmentType::class)
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
                                    ->whereBelongsTo(Filament::getTenant())->where('type', TaxCodeType::WithholdingTax)
                                    ->where('is_active', true)->orderBy('code')->pluck('name', 'id')->all())
                                ->searchable()
                                ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                        ])
                        ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /** @return array<int, string> */
    private static function accountOptions(AccountType $type): array
    {
        return Account::query()->whereBelongsTo(Filament::getTenant())
            ->where('account_type', $type)->where('is_active', true)
            ->where('allows_manual_posting', true)->orderBy('code')->get()
            ->mapWithKeys(fn (Account $account): array => [
                $account->getKey() => "{$account->code} — {$account->name}",
            ])->all();
    }
}
