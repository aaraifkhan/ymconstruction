<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\DocumentClassification;
use App\Models\BankReconciliation;
use App\Models\BankStatement;
use App\Models\CustomerInvoice;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\GoodsReceipt;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\TreasuryTransaction;
use App\Models\VendorBill;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class DocumentForm
{
    /**
     * @return array<int, string>
     */
    public static function acceptedFileTypes(): array
    {
        return [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('reference_number')
                            ->label('Reference number')
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                                    'company_id',
                                    Filament::getTenant()?->getKey(),
                                ),
                            ),
                        Select::make('document_category_id')
                            ->label('Category')
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->whereBelongsTo(Filament::getTenant())
                                    ->where('is_active', true),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('document_scope')
                            ->label('Related record type')
                            ->options([
                                'company' => 'Company',
                                'employee' => 'Employee profile',
                                'employment' => 'Company employment',
                                'project' => 'Project',
                                'journal' => 'Voucher / Journal',
                                'purchase_requisition' => 'Purchase Requisition',
                                'purchase_order' => 'Purchase Order',
                                'goods_receipt' => 'Goods Receipt / Delivery',
                                'inventory_transaction' => 'Inventory Transaction',
                                'vendor_bill' => 'Vendor Bill / Credit Note',
                                'customer_invoice' => 'Customer Invoice / Credit Note',
                                'treasury_transaction' => 'Payment / Receipt / Transfer',
                                'bank_statement' => 'Bank Statement',
                                'bank_reconciliation' => 'Bank Reconciliation',
                            ])
                            ->default('company')
                            ->live()
                            ->required()
                            ->visibleOn('create'),
                        Select::make('related_record_id')
                            ->label('Related record')
                            ->options(function (Get $get): array {
                                $company = Filament::getTenant();

                                return match ($get('document_scope')) {
                                    'employee' => Employee::query()
                                        ->whereHas(
                                            'employments',
                                            fn (Builder $query): Builder => $query->whereBelongsTo($company),
                                        )
                                        ->orderBy('full_name')
                                        ->pluck('full_name', 'id')
                                        ->all(),
                                    'employment' => Employment::query()
                                        ->whereBelongsTo($company)
                                        ->with('employee')
                                        ->get()
                                        ->mapWithKeys(fn (Employment $employment): array => [
                                            $employment->getKey() => "{$employment->employee->full_name} ({$employment->employee_code})",
                                        ])
                                        ->all(),
                                    'project' => Project::query()
                                        ->whereBelongsTo($company)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all(),
                                    'journal' => JournalEntry::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (JournalEntry $entry): array => [
                                            $entry->getKey() => ($entry->voucher_number ?? 'Draft #'.$entry->getKey()).' — '.$entry->description,
                                        ])
                                        ->all(),
                                    'purchase_requisition' => PurchaseRequisition::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (PurchaseRequisition $requisition): array => [
                                            $requisition->getKey() => $requisition->requisition_number ?? 'Draft PR #'.$requisition->getKey(),
                                        ])
                                        ->all(),
                                    'purchase_order' => PurchaseOrder::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (PurchaseOrder $order): array => [
                                            $order->getKey() => $order->purchase_order_number ?? 'Draft PO #'.$order->getKey(),
                                        ])
                                        ->all(),
                                    'goods_receipt' => GoodsReceipt::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (GoodsReceipt $receipt): array => [
                                            $receipt->getKey() => $receipt->goods_receipt_number ?? 'Draft GRN #'.$receipt->getKey(),
                                        ])
                                        ->all(),
                                    'inventory_transaction' => InventoryTransaction::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (InventoryTransaction $transaction): array => [
                                            $transaction->getKey() => $transaction->transaction_number ?? 'Draft inventory #'.$transaction->getKey(),
                                        ])
                                        ->all(),
                                    'vendor_bill' => VendorBill::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (VendorBill $bill): array => [
                                            $bill->getKey() => $bill->vendor_bill_number ?? 'Draft Vendor Bill #'.$bill->getKey(),
                                        ])
                                        ->all(),
                                    'customer_invoice' => CustomerInvoice::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (CustomerInvoice $invoice): array => [
                                            $invoice->getKey() => $invoice->invoice_number ?? 'Draft Customer Invoice #'.$invoice->getKey(),
                                        ])
                                        ->all(),
                                    'treasury_transaction' => TreasuryTransaction::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (TreasuryTransaction $transaction): array => [
                                            $transaction->getKey() => $transaction->transaction_number ?? 'Draft Treasury #'.$transaction->getKey(),
                                        ])
                                        ->all(),
                                    'bank_statement' => BankStatement::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (BankStatement $statement): array => [
                                            $statement->getKey() => $statement->period_start->toDateString().' to '.$statement->period_end->toDateString(),
                                        ])
                                        ->all(),
                                    'bank_reconciliation' => BankReconciliation::query()
                                        ->whereBelongsTo($company)
                                        ->latest()
                                        ->get()
                                        ->mapWithKeys(fn (BankReconciliation $reconciliation): array => [
                                            $reconciliation->getKey() => $reconciliation->period_start->toDateString().' to '.$reconciliation->period_end->toDateString(),
                                        ])
                                        ->all(),
                                    default => [],
                                };
                            })
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => $get('document_scope') !== 'company')
                            ->visible(fn (string $operation, Get $get): bool => $operation === 'create' && $get('document_scope') !== 'company'),
                        Select::make('classification')
                            ->label('Sensitivity')
                            ->options(
                                collect(DocumentClassification::cases())
                                    ->mapWithKeys(fn (DocumentClassification $classification): array => [
                                        $classification->value => $classification->label(),
                                    ])
                                    ->all(),
                            )
                            ->default(DocumentClassification::Internal->value)
                            ->required(),
                        DatePicker::make('issue_date')
                            ->label('Issue date'),
                        DatePicker::make('expiry_date')
                            ->label('Expiry date')
                            ->afterOrEqual('issue_date'),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        KeyValue::make('metadata')
                            ->label('Additional metadata')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Initial file')
                    ->description('The stored file name is generated securely. The original name is retained only as metadata.')
                    ->schema([
                        FileUpload::make('uploaded_file_path')
                            ->label('Document file')
                            ->disk('local')
                            ->directory(
                                fn (): string => 'documents/'.Filament::getTenant()?->getKey().'/incoming',
                            )
                            ->visibility('private')
                            ->storeFileNamesIn('original_file_name')
                            ->acceptedFileTypes(static::acceptedFileTypes())
                            ->rules([
                                'extensions:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt',
                            ])
                            ->maxSize(10240)
                            ->downloadable(false)
                            ->openable(false)
                            ->previewable(false)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->columnSpanFull(),
            ]);
    }
}
