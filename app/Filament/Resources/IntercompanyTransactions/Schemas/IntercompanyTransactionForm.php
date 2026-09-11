<?php

namespace App\Filament\Resources\IntercompanyTransactions\Schemas;

use App\Enums\IntercompanyDirection;
use App\Models\Company;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class IntercompanyTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        $tenant = fn (): ?Company => Filament::getTenant();

        return $schema
            ->columns(1)
            ->components([
                Section::make('Paired Company Transaction Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('counterparty_company_id')
                            ->label('Counterparty Company')
                            ->options(fn (): array => Filament::auth()->user()?->getAccessibleCompanies()
                                ->where('id', '!=', $tenant()?->getKey())->pluck('name', 'id')->all() ?? [])
                            ->searchable()
                            ->live()
                            ->required(),
                        DatePicker::make('transaction_date')
                            ->label('Transaction Date')
                            ->required(),
                        Select::make('direction')
                            ->label('Intercompany Direction')
                            ->options(IntercompanyDirection::class)
                            ->required(),
                        TextInput::make('amount')
                            ->label('Amount (PKR)')
                            ->numeric()
                            ->prefix('PKR')
                            ->minValue(0.0001)
                            ->required(),
                        TextInput::make('reference')
                            ->label('Reference / Doc #')
                            ->maxLength(120),
                        Textarea::make('description')
                            ->label('Description / Particulars')
                            ->required()
                            ->rows(2)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),
                Section::make('Offset Accounts')
                    ->description('The due-from / due-to control accounts are resolved from each company mapping automatically.')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('origin_offset_account_id')
                            ->label('Current Company Offset Account')
                            ->options(fn (): array => $tenant()?->accounts()->where('is_active', true)->where('allows_manual_posting', true)
                                ->orderBy('code')->get()->mapWithKeys(fn ($account): array => [$account->getKey() => "{$account->code} — {$account->name}"])->all() ?? [])
                            ->searchable()
                            ->required(),
                        Select::make('counterparty_offset_account_id')
                            ->label('Counterparty Offset Account')
                            ->options(function (Get $get): array {
                                $companyId = $get('counterparty_company_id');

                                return $companyId ? Company::find($companyId)?->accounts()
                                    ->where('is_active', true)->where('allows_manual_posting', true)->orderBy('code')
                                    ->get()->mapWithKeys(fn ($account): array => [$account->getKey() => "{$account->code} — {$account->name}"])->all() ?? [] : [];
                            })
                            ->searchable()
                            ->required(),
                    ]),
            ]);
    }
}
