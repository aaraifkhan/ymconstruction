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

        return $schema->components([
            Section::make('Category and accounting mappings')->schema([
                TextInput::make('code')->required()->maxLength(30),
                TextInput::make('name')->required()->maxLength(150),
                Select::make('cost_account_id')->label('Asset cost account')->options($accounts)->searchable()->required(),
                Select::make('accumulated_depreciation_account_id')->label('Accumulated depreciation account')->options($accounts)->searchable(),
                Select::make('depreciation_expense_account_id')->label('Depreciation expense account')->options($accounts)->searchable(),
                Select::make('disposal_gain_account_id')->label('Disposal gain account')->options($accounts)->searchable(),
                Select::make('disposal_loss_account_id')->label('Disposal loss account')->options($accounts)->searchable(),
                TextInput::make('default_useful_life_months')->label('Default life (months)')->numeric()->minValue(1)->required(),
                Checkbox::make('is_depreciable')->default(true),
                Checkbox::make('is_active')->default(true),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
