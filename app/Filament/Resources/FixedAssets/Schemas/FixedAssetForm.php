<?php

namespace App\Filament\Resources\FixedAssets\Schemas;

use App\Enums\AssetAcquisitionSource;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FixedAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        $company = fn () => Filament::getTenant();
        $accounts = fn (): array => $company()?->accounts()->where('allows_manual_posting', true)->where('is_active', true)
            ->orderBy('code')->get()->mapWithKeys(fn ($account): array => [$account->getKey() => "{$account->code} — {$account->name}"])->all() ?? [];

        return $schema->components([
            Section::make('Asset identity')->schema([
                TextInput::make('asset_number')->required()->maxLength(50),
                TextInput::make('name')->required()->maxLength(255),
                Select::make('asset_category_id')->label('Category')->options(fn (): array => $company()?->assetCategories()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all() ?? [])->searchable()->required(),
                TextInput::make('serial_number')->maxLength(100),
                TextInput::make('location')->maxLength(255),
                Textarea::make('notes')->label('Private notes')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Acquisition and depreciation')->schema([
                Select::make('acquisition_source')->options(AssetAcquisitionSource::class)->default(AssetAcquisitionSource::Manual->value)->required(),
                Select::make('vendor_bill_line_id')->label('Posted vendor bill line')->relationship('vendorBillLine', 'description')->searchable(),
                Select::make('capitalization_credit_account_id')->label('Manual capitalization credit')->options($accounts)->searchable(),
                DatePicker::make('acquired_on')->required(),
                DatePicker::make('available_for_use_on')->required()->afterOrEqual('acquired_on'),
                TextInput::make('acquisition_cost')->numeric()->minValue(0.0001)->required(),
                TextInput::make('residual_value')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('useful_life_months')->numeric()->minValue(1)->required(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Current assignment')->schema([
                Select::make('custodian_employment_id')->label('Custodian')->options(fn (): array => $company()?->employments()->with('employee')->get()->mapWithKeys(fn ($employment): array => [$employment->getKey() => $employment->employee->full_name])->all() ?? [])->searchable(),
                Select::make('project_id')->options(fn (): array => $company()?->projects()->orderBy('name')->pluck('name', 'id')->all() ?? [])->searchable(),
                Select::make('project_site_id')->options(fn (): array => $company()?->projectSites()->orderBy('name')->pluck('name', 'id')->all() ?? [])->searchable(),
                Select::make('cost_center_id')->options(fn (): array => $company()?->costCenters()->orderBy('name')->pluck('name', 'id')->all() ?? [])->searchable(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
