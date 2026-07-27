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
            Section::make('Voucher')->columns(3)->schema([
                Select::make('voucher_type')->options(collect($types)->mapWithKeys(fn (VoucherType $type) => [$type->value => str($type->value)->headline()]))->required(),
                Select::make('financial_period_id')->relationship('financialPeriod', 'name')->required()->searchable()->preload(),
                DatePicker::make('transaction_date')->required(),
                TextInput::make('reference')->maxLength(120),
                TextInput::make('currency_code')->default('PKR')->required()->length(3),
                Textarea::make('description')->required()->columnSpanFull(),
            ]),
            Section::make('Double-entry lines')->schema([
                Repeater::make('lines')->relationship()->orderColumn('line_number')->minItems(2)->defaultItems(2)
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'company_id' => Filament::getTenant()->getKey()])
                    ->schema([
                        Select::make('account_id')->relationship('account', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true)->whereDoesntHave('children'))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} — {$record->name}")->searchable(['code', 'name'])->preload()->required()->columnSpan(2),
                        TextInput::make('debit')->numeric()->default(0)->minValue(0),
                        TextInput::make('credit')->numeric()->default(0)->minValue(0),
                        Select::make('party_id')->relationship('party', 'name')->searchable()->preload(),
                        Select::make('project_id')->relationship('project', 'name')->searchable()->preload(),
                        Select::make('project_site_id')->relationship('projectSite', 'name')->searchable()->preload(),
                        Select::make('cost_center_id')->relationship('costCenter', 'name')->searchable()->preload(),
                        Select::make('employment_id')->relationship('employment', 'employee_code')->searchable()->preload(),
                        Select::make('company_bank_account_id')->relationship('companyBankAccount', 'bank_name')->searchable()->preload(),
                        TextInput::make('description')->columnSpan(2),
                    ])->columns(4)->columnSpanFull(),
            ]),
        ]);
    }
}
