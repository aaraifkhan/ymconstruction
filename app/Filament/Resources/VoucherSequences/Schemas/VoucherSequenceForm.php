<?php

namespace App\Filament\Resources\VoucherSequences\Schemas;

use App\Enums\VoucherType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VoucherSequenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Voucher Sequence Configuration')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Select::make('financial_year_id')->label('Financial Year')->relationship('financialYear', 'name')->required(),
                        Select::make('voucher_type')->label('Voucher Type')->options(collect(VoucherType::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->headline()]))->required(),
                        TextInput::make('prefix')->label('Sequence Prefix')->required()->maxLength(20),
                        TextInput::make('next_number')->label('Next Sequence Number')->numeric()->minValue(1)->required(),
                        TextInput::make('padding')->label('Digit Padding')->numeric()->minValue(1)->maxValue(12)->required(),
                        Toggle::make('is_active')->label('Is Active')->default(true),
                    ]),
            ]);
    }
}
