<?php

namespace App\Filament\Resources\AssetCategories\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $accounts = fn (): array => Filament::getTenant()?->accounts()
            ->where('allows_manual_posting', true)->where('is_active', true)
            ->orderBy('code')->get()->mapWithKeys(fn ($account): array => [
                $account->getKey() => "{$account->code} — {$account->name}",
            ])->all() ?? [];

        return $schema
            ->columns(1)
            ->components([
                Section::make('Category Identity & Depreciation Settings')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Category Code')
                            ->required()
                            ->maxLength(30),
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('default_useful_life_months')
                            ->label('Default Useful Life (Months)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Select::make('cost_account_id')
                            ->label('Asset Cost GL Account')
                            ->options($accounts)
                            ->searchable()
                            ->required(),
                        Select::make('accumulated_depreciation_account_id')
                            ->label('Accumulated Depreciation GL Account')
                            ->options($accounts)
                            ->searchable(),
                        Select::make('depreciation_expense_account_id')
                            ->label('Depreciation Expense GL Account')
                            ->options($accounts)
                            ->searchable(),
                        Select::make('disposal_gain_account_id')
                            ->label('Disposal Gain GL Account')
                            ->options($accounts)
                            ->searchable(),
                        Select::make('disposal_loss_account_id')
                            ->label('Disposal Loss GL Account')
                            ->options($accounts)
                            ->searchable(),
                        Checkbox::make('is_depreciable')
                            ->label('Is Depreciable Asset')
                            ->default(true),
                        Checkbox::make('is_active')
                            ->label('Is Active')
                            ->default(true),
                    ]),
            ]);
    }
}
