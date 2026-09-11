<?php

namespace App\Filament\Resources\OpeningBalanceMigrations\Schemas;

use App\Enums\OpeningBalanceMigrationStatus;
use App\Filament\Support\CompanyContextField;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OpeningBalanceMigrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Opening Balance Migration Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('financial_year_id')
                            ->relationship('financialYear', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->required(),
                        Select::make('financial_period_id')
                            ->relationship('financialPeriod', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
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
                            ->prefix('PKR')
                            ->default(0),
                        TextInput::make('source_credit_total')
                            ->required()
                            ->numeric()
                            ->prefix('PKR')
                            ->default(0),
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
                        Select::make('reversal_entry_id')
                            ->relationship('reversalEntry', 'id'),
                        Textarea::make('validation_summary')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('reversal_reason')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
