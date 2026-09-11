<?php

namespace App\Filament\Resources\YearEndClosings\Schemas;

use App\Enums\YearEndClosingStatus;
use App\Filament\Support\CompanyContextField;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class YearEndClosingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Year-End Closing Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('financial_year_id')
                            ->relationship('financialYear', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->required(),
                        TextInput::make('idempotency_key')
                            ->required(),
                        Select::make('status')
                            ->options(YearEndClosingStatus::class)
                            ->default('draft')
                            ->required(),
                        TextInput::make('profit_or_loss')
                            ->label('Profit / Loss (PKR)')
                            ->prefix('PKR')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('calculation_checksum'),
                        Select::make('retained_earnings_account_id')
                            ->relationship('retainedEarningsAccount', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
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
                        Select::make('reversal_entry_id')
                            ->relationship('reversalEntry', 'id'),
                        Textarea::make('calculation_snapshot')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('reversal_reason')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
