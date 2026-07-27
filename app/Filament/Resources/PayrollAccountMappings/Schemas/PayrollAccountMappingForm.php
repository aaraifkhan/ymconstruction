<?php

namespace App\Filament\Resources\PayrollAccountMappings\Schemas;

use App\Enums\AccountType;
use App\Enums\PayrollAccountComponent;
use App\Models\Account;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PayrollAccountMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('component')->options(PayrollAccountComponent::class)->required(),
                Select::make('account_id')->label('GL account')
                    ->options(fn (): array => Account::query()->whereBelongsTo(Filament::getTenant())
                        ->whereIn('account_type', [AccountType::Expense, AccountType::Liability])
                        ->where('is_active', true)->where('allows_manual_posting', true)
                        ->orderBy('code')->get()->mapWithKeys(fn (Account $account): array => [
                            $account->getKey() => "{$account->code} — {$account->name}",
                        ])->all())->searchable()->required(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
