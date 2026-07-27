<?php

namespace App\Filament\Resources\TreasuryTransactions\Schemas;

use App\Enums\AccountingMappingKey;
use App\Enums\CustomerInvoiceStatus;
use App\Enums\CustomerInvoiceType;
use App\Enums\TreasuryAllocationType;
use App\Enums\TreasuryCounterpartyType;
use App\Enums\TreasuryInstrumentType;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryTransactionType;
use App\Enums\VendorBillStatus;
use App\Enums\VendorBillType;
use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\CompanyBankAccount;
use App\Models\CustomerInvoice;
use App\Models\Employment;
use App\Models\Party;
use App\Models\PayrollEntry;
use App\Models\VendorBill;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TreasuryTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cash / bank transaction')->columns(4)->schema([
                Select::make('type')->options(TreasuryTransactionType::class)
                    ->default(TreasuryTransactionType::Payment)->live()->required(),
                Select::make('purpose')->options(TreasuryPurpose::class)
                    ->default(TreasuryPurpose::Settlement)->live()->required(),
                DatePicker::make('transaction_date')->default(today())->required(),
                DatePicker::make('value_date'),
                Select::make('counterparty_type')->options(TreasuryCounterpartyType::class)->live(),
                Select::make('party_id')->label('Party')
                    ->options(fn (): array => Party::query()->whereBelongsTo(Filament::getTenant())
                        ->active()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()->visible(fn (Get $get): bool => $get('counterparty_type') === TreasuryCounterpartyType::Party->value),
                Select::make('employment_id')->label('Employee / Employment')
                    ->options(fn (): array => Employment::query()->whereBelongsTo(Filament::getTenant())
                        ->with('employee')->get()->mapWithKeys(fn (Employment $employment): array => [
                            $employment->getKey() => "{$employment->employee->full_name} ({$employment->employee_code})",
                        ])->all())
                    ->searchable()->visible(fn (Get $get): bool => $get('counterparty_type') === TreasuryCounterpartyType::Employment->value),
                TextInput::make('amount')->numeric()->minValue(0.0001)->required(),
                Select::make('source_account_id')->label('Source / credit-side account')
                    ->options(fn (Get $get): array => $get('type') === TreasuryTransactionType::Receipt->value
                        && in_array($get('purpose'), [TreasuryPurpose::Refund->value, TreasuryPurpose::Other->value], true)
                            ? self::postingAccountOptions()
                            : self::liquidAccountOptions())
                    ->searchable()->required(fn (Get $get): bool => $get('type') !== TreasuryTransactionType::Receipt->value
                        || in_array($get('purpose'), [TreasuryPurpose::Refund->value, TreasuryPurpose::Other->value], true)),
                Select::make('source_company_bank_account_id')->label('Source bank account')
                    ->options(fn (): array => self::bankAccountOptions())->searchable()
                    ->visible(fn (Get $get): bool => in_array($get('type'), [
                        TreasuryTransactionType::Payment->value,
                        TreasuryTransactionType::Transfer->value,
                    ], true)),
                Select::make('destination_account_id')->label('Destination / debit-side account')
                    ->options(fn (Get $get): array => $get('type') === TreasuryTransactionType::Payment->value
                        && in_array($get('purpose'), [TreasuryPurpose::Refund->value, TreasuryPurpose::Other->value], true)
                            ? self::postingAccountOptions()
                            : self::liquidAccountOptions())
                    ->searchable()->required(fn (Get $get): bool => $get('type') !== TreasuryTransactionType::Payment->value
                        || in_array($get('purpose'), [TreasuryPurpose::Refund->value, TreasuryPurpose::Other->value], true)),
                Select::make('destination_company_bank_account_id')->label('Destination bank account')
                    ->options(fn (): array => self::bankAccountOptions())->searchable()
                    ->visible(fn (Get $get): bool => in_array($get('type'), [
                        TreasuryTransactionType::Receipt->value,
                        TreasuryTransactionType::Transfer->value,
                    ], true)),
                Select::make('instrument_type')->options(TreasuryInstrumentType::class)
                    ->default(TreasuryInstrumentType::Electronic)->live()->required(),
                TextInput::make('instrument_number')->maxLength(100),
                DatePicker::make('instrument_date'),
                TextInput::make('bank_reference')->maxLength(255),
                TextInput::make('external_reference')->maxLength(255),
                TextInput::make('currency_code')->default('PKR')->disabled()->dehydrated(),
                Textarea::make('description')->required()->columnSpanFull(),
                Textarea::make('notes')->columnSpanFull(),
            ]),
            Section::make('Open-item allocations')
                ->description('Allocate Vendor or Payroll payments, or Customer receipts, against posted open items.')
                ->visible(fn (Get $get): bool => in_array($get('type'), [
                    TreasuryTransactionType::Payment->value,
                    TreasuryTransactionType::Receipt->value,
                ], true) && $get('purpose') === TreasuryPurpose::Settlement->value)
                ->schema([
                    Repeater::make('allocations')->relationship()->defaultItems(0)
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data, Get $get): array => [
                            ...$data,
                            'company_id' => Filament::getTenant()->getKey(),
                            'allocatable_type' => match (true) {
                                $get('../../type') === TreasuryTransactionType::Receipt->value => CustomerInvoice::class,
                                filled($get('../../employment_id')) => PayrollEntry::class,
                                default => VendorBill::class,
                            },
                            'allocation_type' => match (true) {
                                $get('../../type') === TreasuryTransactionType::Receipt->value => TreasuryAllocationType::CustomerInvoice,
                                filled($get('../../employment_id')) => TreasuryAllocationType::PayrollEntry,
                                default => TreasuryAllocationType::VendorBill,
                            },
                        ])->schema([
                            Hidden::make('allocatable_type'),
                            Hidden::make('allocation_type'),
                            Select::make('allocatable_id')->label('Posted open item')
                                ->options(fn (Get $get): array => match (true) {
                                    $get('../../type') === TreasuryTransactionType::Receipt->value => CustomerInvoice::query()->whereBelongsTo(Filament::getTenant())
                                        ->where('customer_id', $get('../../party_id'))
                                        ->where('type', CustomerInvoiceType::Invoice)
                                        ->where('status', CustomerInvoiceStatus::Posted)
                                        ->orderBy('due_date')->get()
                                        ->filter(fn (CustomerInvoice $invoice): bool => bccomp($invoice->postedOpenAmount(), '0', 4) === 1)
                                        ->mapWithKeys(fn (CustomerInvoice $invoice): array => [
                                            $invoice->getKey() => ($invoice->invoice_number ?? '#'.$invoice->getKey()).' — PKR '.$invoice->postedOpenAmount(),
                                        ])->all(),
                                    filled($get('../../employment_id')) => PayrollEntry::query()->whereBelongsTo(Filament::getTenant())
                                        ->where('employment_id', $get('../../employment_id'))
                                        ->whereHas('payrollRun', fn ($query) => $query
                                            ->whereNotNull('journal_entry_id')->whereNull('reversal_journal_entry_id'))
                                        ->with('payrollRun')->get()
                                        ->filter(fn (PayrollEntry $entry): bool => bccomp($entry->postedOpenAmount(), '0', 4) === 1)
                                        ->mapWithKeys(fn (PayrollEntry $entry): array => [
                                            $entry->getKey() => "{$entry->payrollRun->reference_number} — PKR {$entry->postedOpenAmount()}",
                                        ])->all(),
                                    default => VendorBill::query()->whereBelongsTo(Filament::getTenant())
                                        ->where('vendor_id', $get('../../party_id'))
                                        ->where('type', VendorBillType::Invoice)
                                        ->where('status', VendorBillStatus::Posted)
                                        ->orderBy('due_date')->get()
                                        ->filter(fn (VendorBill $bill): bool => bccomp($bill->postedOpenAmount(), '0', 4) === 1)
                                        ->mapWithKeys(fn (VendorBill $bill): array => [
                                            $bill->getKey() => ($bill->vendor_bill_number ?? '#'.$bill->getKey()).' — PKR '.$bill->postedOpenAmount(),
                                        ])->all(),
                                })
                                ->searchable()->required(),
                            TextInput::make('amount')->numeric()->minValue(0.0001)->required(),
                        ])->columns(2),
                ]),
        ]);
    }

    /** @return array<int, string> */
    private static function liquidAccountOptions(): array
    {
        return AccountingMapping::query()->whereBelongsTo(Filament::getTenant())
            ->where(function ($query): void {
                $query->where('system_key', AccountingMappingKey::DefaultCash)
                    ->orWhereNotNull('company_bank_account_id');
            })
            ->where('is_active', true)->with(['account', 'bankAccount'])->get()
            ->mapWithKeys(fn (AccountingMapping $mapping): array => [
                $mapping->account_id => $mapping->bankAccount === null
                    ? "{$mapping->account->code} — Cash"
                    : "{$mapping->account->code} — {$mapping->bankAccount->bank_name} / {$mapping->bankAccount->maskedAccountNumber()}",
            ])->all();
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

    /** @return array<int, string> */
    private static function bankAccountOptions(): array
    {
        return CompanyBankAccount::query()->whereBelongsTo(Filament::getTenant())
            ->where('is_active', true)->orderBy('bank_name')->get()
            ->mapWithKeys(fn (CompanyBankAccount $bank): array => [
                $bank->getKey() => "{$bank->bank_name} — {$bank->maskedAccountNumber()}",
            ])->all();
    }
}
