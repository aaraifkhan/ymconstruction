<?php

namespace App\Filament\Resources\FinalSettlementAccountMappings\Schemas;

use App\Enums\FinalSettlementComponentType;
use App\Models\Account;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FinalSettlementAccountMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('component_type')
                    ->options(collect(FinalSettlementComponentType::cases())
                        ->reject->usesEmployeeAdvancesMapping()
                        ->mapWithKeys(fn (FinalSettlementComponentType $type): array => [$type->value => $type->label()])
                        ->all())
                    ->required(),
                Select::make('account_id')->label('Posting account')
                    ->options(fn (): array => Account::query()->whereBelongsTo(Filament::getTenant())
                        ->where('is_active', true)->where('allows_manual_posting', true)
                        ->orderBy('code')->get()->mapWithKeys(fn (Account $account): array => [
                            $account->getKey() => "{$account->code} — {$account->name}",
                        ])->all())
                    ->searchable()->required(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
