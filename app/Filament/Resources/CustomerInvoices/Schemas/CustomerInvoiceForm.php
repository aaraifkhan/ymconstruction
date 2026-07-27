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
            Section::make('Customer document')->columns(4)->schema([
                Select::make('type')->options(CustomerInvoiceType::class)->default(CustomerInvoiceType::Invoice)->live()->required(),
                Select::make('category')->options(CustomerInvoiceCategory::class)->default(CustomerInvoiceCategory::ServiceInvoice)->live()->required(),
                Select::make('original_customer_invoice_id')->label('Original posted invoice')
                    ->options(fn (): array => CustomerInvoice::query()->whereBelongsTo(Filament::getTenant())
                        ->where('type', CustomerInvoiceType::Invoice)->where('status', CustomerInvoiceStatus::Posted)
                        ->latest()->pluck('invoice_number', 'id')->all())
                    ->searchable()->visible(fn (Get $get): bool => $get('type') === CustomerInvoiceType::CreditNote->value)
                    ->required(fn (Get $get): bool => $get('type') === CustomerInvoiceType::CreditNote->value),
                Select::make('customer_id')->options(fn (): array => Party::query()->whereBelongsTo(Filament::getTenant())
                    ->active()->get()->filter(fn (Party $party): bool => $party->hasRole(PartyRole::Customer))
                    ->sortBy('name')->pluck('name', 'id')->all())->searchable()->required(),
                TextInput::make('customer_reference')->maxLength(255),
                DatePicker::make('invoice_date')->default(today())->required(),
                DatePicker::make('due_date')->default(today()->addDays(30))->required(),
                Select::make('project_id')->options(fn (): array => Project::query()->whereBelongsTo(Filament::getTenant())
                    ->orderBy('name')->pluck('name', 'id')->all())->searchable()
                    ->required(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                Select::make('project_site_id')->options(fn (): array => ProjectSite::query()->whereBelongsTo(Filament::getTenant())
                    ->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                TextInput::make('certificate_number')->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                DatePicker::make('certificate_date')->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                TextInput::make('work_value')->numeric()->default(0)
                    ->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                TextInput::make('variation_amount')->numeric()->default(0)
                    ->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value),
                TextInput::make('currency_code')->default('PKR')->disabled()->dehydrated(),
                Textarea::make('description')->columnSpanFull(),
            ]),
            Section::make('Revenue lines')->schema([
                Repeater::make('lines')->relationship()->orderColumn('line_number')->minItems(1)->defaultItems(1)
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                        ...$data,
                        'company_id' => Filament::getTenant()->getKey(),
                    ])->schema([
                        Select::make('original_customer_invoice_line_id')->label('Original invoice line')
                            ->options(fn (Get $get): array => CustomerInvoiceLine::query()
                                ->where('customer_invoice_id', $get('../../../original_customer_invoice_id'))
                                ->orderBy('line_number')->pluck('item_name_snapshot', 'id')->all())
                            ->searchable()->visible(fn (Get $get): bool => $get('../../../type') === CustomerInvoiceType::CreditNote->value),
                        Select::make('item_id')->options(fn (): array => Item::query()->whereBelongsTo(Filament::getTenant())
                            ->active()->orderBy('name')->get()->mapWithKeys(fn (Item $item): array => [
                                $item->getKey() => "{$item->code} — {$item->name}",
                            ])->all())->searchable(),
                        Select::make('unit_of_measure_id')->label('UOM')->options(fn (): array => UnitOfMeasure::query()
                            ->whereBelongsTo(Filament::getTenant())->active()->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                        TextInput::make('item_name_snapshot')->label('Description')->required(),
                        TextInput::make('quantity')->numeric()->minValue(0.0001)->default(1)->required(),
                        TextInput::make('unit_rate')->numeric()->minValue(0)->required(),
                        Select::make('revenue_account_id')->label('Revenue account')
                            ->options(fn (): array => self::accountOptions(AccountType::Revenue))->searchable()->required(),
                        Select::make('tax_code_id')->label('Sales Tax code')->options(fn (): array => TaxCode::query()
                            ->whereBelongsTo(Filament::getTenant())->where('type', TaxCodeType::SalesTax)
                            ->where('is_active', true)->orderBy('code')->pluck('name', 'id')->all())->searchable(),
                        Select::make('inventory_site_id')->label('Inventory site')->options(fn (): array => ProjectSite::query()
                            ->whereBelongsTo(Filament::getTenant())->orderBy('name')->pluck('name', 'id')->all())->searchable()
                            ->visible(fn (Get $get): bool => $get('../../../category') === CustomerInvoiceCategory::TradingSale->value),
                        Select::make('cogs_account_id')->label('COGS account')
                            ->options(fn (): array => self::accountOptions(AccountType::Expense))->searchable()
                            ->visible(fn (Get $get): bool => $get('../../../category') === CustomerInvoiceCategory::TradingSale->value),
                        Textarea::make('description')->columnSpanFull(),
                    ])->columns(4),
            ]),
            Section::make('Running-bill deductions')
                ->visible(fn (Get $get): bool => $get('category') === CustomerInvoiceCategory::RunningBill->value)
                ->schema([
                    Repeater::make('adjustments')->relationship()->defaultItems(0)
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [
                            ...$data,
                            'company_id' => Filament::getTenant()->getKey(),
                        ])->schema([
                            Select::make('type')->options(CustomerInvoiceAdjustmentType::class)->required(),
                            TextInput::make('description')->required(),
                            Select::make('tax_code_id')->label('WHT code')->options(fn (): array => TaxCode::query()
                                ->whereBelongsTo(Filament::getTenant())->where('type', TaxCodeType::WithholdingTax)
                                ->where('is_active', true)->orderBy('code')->pluck('name', 'id')->all())->searchable(),
                            TextInput::make('amount')->numeric()->minValue(0.0001)->required(),
                        ])->columns(4),
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
