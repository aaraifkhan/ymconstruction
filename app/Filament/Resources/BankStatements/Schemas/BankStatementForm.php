<?php

namespace App\Filament\Resources\BankStatements\Schemas;

use App\Models\CompanyBankAccount;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankStatementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bank statement period')->columns(3)->schema([
                Select::make('company_bank_account_id')->label('Company bank account')
                    ->options(fn (): array => CompanyBankAccount::query()
                        ->whereBelongsTo(Filament::getTenant())->where('is_active', true)
                        ->orderBy('bank_name')->get()->mapWithKeys(fn (CompanyBankAccount $bank): array => [
                            $bank->getKey() => "{$bank->bank_name} — {$bank->maskedAccountNumber()}",
                        ])->all())->searchable()->required(),
                DatePicker::make('period_start')->required(),
                DatePicker::make('period_end')->afterOrEqual('period_start')->required(),
                TextInput::make('opening_balance')->numeric()->required(),
                TextInput::make('closing_balance')->numeric()->required(),
                TextInput::make('currency_code')->default('PKR')->disabled()->dehydrated(),
            ]),
        ]);
    }
}
