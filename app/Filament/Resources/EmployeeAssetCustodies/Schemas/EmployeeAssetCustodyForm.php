<?php

namespace App\Filament\Resources\EmployeeAssetCustodies\Schemas;

use App\Enums\AssetStatus;
use App\Models\Employment;
use App\Models\FixedAsset;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeAssetCustodyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Asset Custody & Handover Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Select::make('fixed_asset_id')
                            ->label('Fixed Asset / Device')
                            ->options(fn (): array => FixedAsset::query()->whereBelongsTo(Filament::getTenant())
                                ->where('status', AssetStatus::Active)->orderBy('asset_number')->get()
                                ->mapWithKeys(fn (FixedAsset $asset): array => [
                                    $asset->getKey() => "{$asset->asset_number} — {$asset->name}",
                                ])->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('employment_id')
                            ->label('Custodian (Employee)')
                            ->options(fn (): array => Employment::query()->whereBelongsTo(Filament::getTenant())
                                ->with('employee')->orderBy('employee_code')->get()
                                ->mapWithKeys(fn (Employment $employment): array => [
                                    $employment->getKey() => "{$employment->employee_code} — {$employment->employee->full_name}",
                                ])->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('issued_on')
                            ->label('Date Issued')
                            ->default(now())
                            ->required(),
                        DatePicker::make('due_on')
                            ->label('Expected Return Date'),
                        TextInput::make('issued_condition')
                            ->label('Physical Condition at Handover')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('issued_location')
                            ->label('Issued Work Location / Site')
                            ->maxLength(255),
                        TagsInput::make('accessories')
                            ->label('Included Accessories (Charger, Bag, Mouse etc.)')
                            ->columnSpanFull(),
                        Textarea::make('issue_notes')
                            ->label('Handover Remarks / Serial Details')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
