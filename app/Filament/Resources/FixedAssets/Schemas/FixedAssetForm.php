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
            Section::make('Asset Identity & Specifications')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    TextInput::make('asset_number')
                        ->label('Asset Tag / Number')
                        ->required()
                        ->maxLength(50),
                    TextInput::make('name')
                        ->label('Asset Name / Description')
                        ->required()
                        ->maxLength(255),
                    Select::make('asset_category_id')
                        ->label('Asset Category')
                        ->options(fn (): array => $company()?->assetCategories()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all() ?? [])
                        ->searchable()
                        ->required(),
                    TextInput::make('serial_number')
                        ->label('Serial / Chassis Number')
                        ->maxLength(100),
                    TextInput::make('location')
                        ->label('Physical Location')
                        ->maxLength(255),
                    Textarea::make('notes')
                        ->label('Private Notes / Specs')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            Section::make('Acquisition & Depreciation Parameters')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Select::make('acquisition_source')
                        ->label('Acquisition Source')
                        ->options(AssetAcquisitionSource::class)
                        ->default(AssetAcquisitionSource::Manual->value)
                        ->required(),
                    Select::make('vendor_bill_line_id')
                        ->label('Posted Vendor Bill Line')
                        ->relationship('vendorBillLine', 'description')
                        ->searchable(),
                    Select::make('capitalization_credit_account_id')
                        ->label('Manual Capitalization Credit Account')
                        ->options($accounts)
                        ->searchable(),
                    DatePicker::make('acquired_on')
                        ->label('Acquired Date')
                        ->required(),
                    DatePicker::make('available_for_use_on')
                        ->label('Available for Use Date')
                        ->required()
                        ->afterOrEqual('acquired_on'),
                    TextInput::make('acquisition_cost')
                        ->label('Acquisition Cost (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0.0001)
                        ->required(),
                    TextInput::make('residual_value')
                        ->label('Residual / Salvage Value (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    TextInput::make('useful_life_months')
                        ->label('Useful Life (Months)')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                ]),
            Section::make('Current Assignment & Location')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                ->columnSpanFull()
                ->schema([
                    Select::make('custodian_employment_id')
                        ->label('Custodian Employee')
                        ->options(fn (): array => $company()?->employments()->with('employee')->get()->mapWithKeys(fn ($employment): array => [$employment->getKey() => $employment->employee->full_name])->all() ?? [])
                        ->searchable(),
                    Select::make('project_id')
                        ->label('Assigned Project')
                        ->options(fn (): array => $company()?->projects()->orderBy('name')->pluck('name', 'id')->all() ?? [])
                        ->searchable(),
                    Select::make('project_site_id')
                        ->label('Assigned Project Site')
                        ->options(fn (): array => $company()?->projectSites()->orderBy('name')->pluck('name', 'id')->all() ?? [])
                        ->searchable(),
                    Select::make('cost_center_id')
                        ->label('Assigned Cost Center')
                        ->options(fn (): array => $company()?->costCenters()->orderBy('name')->pluck('name', 'id')->all() ?? [])
                        ->searchable(),
                ]),
        ]);
    }
}
