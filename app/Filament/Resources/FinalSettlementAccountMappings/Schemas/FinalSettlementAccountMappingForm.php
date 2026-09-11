<?php

namespace App\Filament\Resources\FinalSettlementAccountMappings\Schemas;

use App\Enums\FinalSettlementComponentType;
use App\Models\Account;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinalSettlementAccountMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Final Settlement GL Mapping')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Select::make('component_type')
                            ->label('Settlement Component')
                            ->options(collect(FinalSettlementComponentType::cases())
                                ->reject->usesEmployeeAdvancesMapping()
                                ->mapWithKeys(fn (FinalSettlementComponentType $type): array => [$type->value => $type->label()])
                                ->all())
                            ->required(),
                        Select::make('account_id')->label('Posting GL Account')
                            ->options(fn (): array => Account::query()->whereBelongsTo(Filament::getTenant())
                                ->where('is_active', true)->where('allows_manual_posting', true)
                                ->orderBy('code')->get()->mapWithKeys(fn (Account $account): array => [
                                    $account->getKey() => "{$account->code} — {$account->name}",
                                ])->all())
                            ->searchable()->required()->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                        Toggle::make('is_active')->label('Is Active')->default(true),
                    ]),
            ]);
    }
}
