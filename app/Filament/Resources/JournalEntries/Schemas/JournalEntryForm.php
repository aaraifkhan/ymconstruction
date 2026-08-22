<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use App\Enums\VoucherType;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        $types = [VoucherType::Journal, VoucherType::Payment, VoucherType::Receipt, VoucherType::Contra, VoucherType::DebitNote, VoucherType::CreditNote];

        return $schema->components([
            Section::make('Voucher Details')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->schema([
                    Select::make('voucher_type')
                        ->label('Voucher Type')
                        ->options(collect($types)->mapWithKeys(fn (VoucherType $type) => [$type->value => str($type->value)->headline()]))
                        ->required(),
                    Select::make('financial_period_id')
                        ->label('Financial Period')
                        ->relationship('financialPeriod', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    DatePicker::make('transaction_date')
                        ->label('Transaction Date')
                        ->required()
                        ->default(today()),
                    TextInput::make('reference')
                        ->label('Reference / Cheque No.')
                        ->maxLength(120),
                    TextInput::make('currency_code')
                        ->label('Currency')
                        ->default('PKR')
                        ->required()
                        ->length(3),
                    Textarea::make('description')
                        ->label('Narration / Description')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            Section::make('Double-Entry Voucher Lines')
                ->schema([
                    Repeater::make('lines')
                        ->relationship()
                        ->orderColumn('line_number')
                        ->minItems(2)
                        ->defaultItems(2)
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'company_id' => Filament::getTenant()->getKey()])
                        ->schema([
                            Select::make('account_id')
                                ->label('Account Head')
                                ->relationship('account', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true)->whereDoesntHave('children'))
                                ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} — {$record->name}")
                                ->searchable(['code', 'name'])
                                ->preload()
                                ->required()
                                ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                            TextInput::make('debit')
                                ->label('Debit (PKR)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->prefix('PKR'),
                            TextInput::make('credit')
                                ->label('Credit (PKR)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->prefix('PKR'),
                            Select::make('party_id')
                                ->label('Party / Customer / Vendor')
                                ->relationship('party', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('project_id')
                                ->label('Project')
                                ->relationship('project', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('project_site_id')
                                ->label('Project Site')
                                ->relationship('projectSite', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('company_bank_account_id')
                                ->label('Bank Account')
                                ->relationship('companyBankAccount', 'bank_name')
                                ->searchable()
                                ->preload(),
                            Select::make('cost_center_id')
                                ->label('Cost Center')
                                ->relationship('costCenter', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('employment_id')
                                ->label('Employee')
                                ->relationship('employment', 'employee_code')
                                ->searchable()
                                ->preload(),
                            TextInput::make('description')
                                ->label('Line Particulars')
                                ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                        ])
                        ->columns(['sm' => 1, 'md' => 2, 'lg' => 4])
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
