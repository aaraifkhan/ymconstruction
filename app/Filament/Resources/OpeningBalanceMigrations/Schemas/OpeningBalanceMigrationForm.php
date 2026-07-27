<?php

namespace App\Filament\Resources\OpeningBalanceMigrations\Schemas;

use App\Enums\OpeningBalanceMigrationStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OpeningBalanceMigrationForm
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
                Select::make('financial_period_id')
                    ->relationship('financialPeriod', 'name')
                    ->required(),
                DatePicker::make('opening_date')
                    ->required(),
                TextInput::make('idempotency_key')
                    ->required(),
                TextInput::make('source_filename')
                    ->required(),
                TextInput::make('source_checksum')
                    ->required(),
                Select::make('status')
                    ->options(OpeningBalanceMigrationStatus::class)
                    ->default('draft')
                    ->required(),
                TextInput::make('row_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('valid_row_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('source_debit_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('source_credit_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('validation_summary')
                    ->columnSpanFull(),
                TextInput::make('prepared_by_id')
                    ->required()
                    ->numeric(),
                TextInput::make('validated_by_id')
                    ->numeric(),
                DateTimePicker::make('validated_at'),
                TextInput::make('imported_by_id')
                    ->numeric(),
                DateTimePicker::make('imported_at'),
                Select::make('opening_balance_batch_id')
                    ->relationship('openingBalanceBatch', 'id'),
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
