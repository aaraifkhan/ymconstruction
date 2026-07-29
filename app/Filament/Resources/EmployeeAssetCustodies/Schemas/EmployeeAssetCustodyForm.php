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
use Filament\Schemas\Schema;

class EmployeeAssetCustodyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('fixed_asset_id')
                    ->options(fn (): array => FixedAsset::query()->whereBelongsTo(Filament::getTenant())
                        ->where('status', AssetStatus::Active)->orderBy('asset_number')->get()
                        ->mapWithKeys(fn (FixedAsset $asset): array => [
                            $asset->getKey() => "{$asset->asset_number} — {$asset->name}",
                        ])->all())->searchable()->required(),
                Select::make('employment_id')
                    ->options(fn (): array => Employment::query()->whereBelongsTo(Filament::getTenant())
                        ->with('employee')->orderBy('employee_code')->get()
                        ->mapWithKeys(fn (Employment $employment): array => [
                            $employment->getKey() => "{$employment->employee_code} — {$employment->employee->full_name}",
                        ])->all())->searchable()->required(),
                DatePicker::make('issued_on')->default(now())->required(),
                DatePicker::make('due_on'),
                TextInput::make('issued_condition')->required()->maxLength(255),
                TextInput::make('issued_location')->maxLength(255),
                TagsInput::make('accessories')->columnSpanFull(),
                Textarea::make('issue_notes')->columnSpanFull(),
            ]);
    }
}
