<?php

namespace App\Filament\Resources\AssetDisposals\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetDisposalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asset disposal proposal')->schema([
                Select::make('fixed_asset_id')->label('Active asset')
                    ->options(fn (): array => Filament::getTenant()?->fixedAssets()->where('status', 'active')->orderBy('asset_number')->get()
                        ->mapWithKeys(fn ($asset): array => [$asset->getKey() => "{$asset->asset_number} — {$asset->name}"])->all() ?? [])
                    ->searchable()->required(),
                DatePicker::make('disposal_date')->required(),
                TextInput::make('proceeds_amount')->numeric()->minValue(0)->default(0)->required(),
                Select::make('proceeds_account_id')->label('Proceeds cash/bank account')
                    ->options(fn (): array => Filament::getTenant()?->accounts()->where('allows_manual_posting', true)->where('is_active', true)
                        ->orderBy('code')->get()->mapWithKeys(fn ($account): array => [$account->getKey() => "{$account->code} — {$account->name}"])->all() ?? [])
                    ->searchable(),
                Textarea::make('reason')->required()->maxLength(2000)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
