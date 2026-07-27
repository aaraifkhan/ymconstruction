<?php

namespace App\Filament\Resources\YearEndClosings\Schemas;

use App\Enums\YearEndClosingStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class YearEndClosingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('financial_year_id')
                    ->relationship('financialYear', 'name')
                    ->required(),
                TextInput::make('idempotency_key')
                    ->required(),
                Select::make('status')
                    ->options(YearEndClosingStatus::class)
                    ->default('draft')
                    ->required(),
                TextInput::make('profit_or_loss')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('calculation_checksum'),
                Textarea::make('calculation_snapshot')
                    ->columnSpanFull(),
                Select::make('retained_earnings_account_id')
                    ->relationship('retainedEarningsAccount', 'name')
                    ->required(),
                TextInput::make('prepared_by_id')
                    ->required()
                    ->numeric(),
                TextInput::make('approved_by_id')
                    ->numeric(),
                DateTimePicker::make('approved_at'),
                TextInput::make('posted_by_id')
                    ->numeric(),
                DateTimePicker::make('posted_at'),
                Select::make('journal_entry_id')
                    ->relationship('journalEntry', 'id'),
                TextInput::make('reversed_by_id')
                    ->numeric(),
                DateTimePicker::make('reversed_at'),
                Textarea::make('reversal_reason')
                    ->columnSpanFull(),
                Select::make('reversal_entry_id')
                    ->relationship('reversalEntry', 'id'),
            ]);
    }
}
