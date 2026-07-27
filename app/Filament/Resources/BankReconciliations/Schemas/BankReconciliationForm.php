<?php

namespace App\Filament\Resources\BankReconciliations\Schemas;

use App\Enums\BankStatementStatus;
use App\Models\BankStatement;
use App\Models\CompanyBankAccount;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankReconciliationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reconciliation source')->columns(2)->schema([
                Select::make('company_bank_account_id')->label('Bank account')
                    ->options(fn (): array => CompanyBankAccount::query()
                        ->whereBelongsTo(Filament::getTenant())->where('is_active', true)
                        ->orderBy('bank_name')->get()->mapWithKeys(fn (CompanyBankAccount $bank): array => [
                            $bank->getKey() => "{$bank->bank_name} — {$bank->maskedAccountNumber()}",
                        ])->all())->searchable()->required(),
                Select::make('bank_statement_id')->label('Imported statement')
                    ->options(fn (): array => BankStatement::query()
                        ->whereBelongsTo(Filament::getTenant())
                        ->whereIn('status', [BankStatementStatus::Imported, BankStatementStatus::Locked])
                        ->doesntHave('reconciliation')->orderByDesc('period_end')->get()
                        ->mapWithKeys(fn (BankStatement $statement): array => [
                            $statement->getKey() => $statement->period_start->toDateString().' to '.$statement->period_end->toDateString(),
                        ])->all())->searchable()->required(),
                DatePicker::make('period_start')->required(),
                DatePicker::make('period_end')->afterOrEqual('period_start')->required(),
            ]),
        ]);
    }
}
