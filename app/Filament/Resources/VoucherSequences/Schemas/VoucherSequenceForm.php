<?php

namespace App\Filament\Resources\VoucherSequences\Schemas;

use App\Enums\VoucherType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VoucherSequenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('financial_year_id')->relationship('financialYear', 'name')->required(),
                Select::make('voucher_type')->options(collect(VoucherType::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->headline()]))->required(),
                TextInput::make('prefix')->required()->maxLength(20),
                TextInput::make('next_number')->numeric()->minValue(1)->required(),
                TextInput::make('padding')->numeric()->minValue(1)->maxValue(12)->required(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
