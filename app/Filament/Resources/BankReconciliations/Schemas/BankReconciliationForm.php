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
            Section::make('Reconciliation Source & Period')
                ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                ->columnSpanFull()
                ->schema([
                    Select::make('company_bank_account_id')
                        ->label('Bank Account')
                        ->options(fn (): array => CompanyBankAccount::query()
                            ->whereBelongsTo(Filament::getTenant())->where('is_active', true)
                            ->orderBy('bank_name')->get()->mapWithKeys(fn (CompanyBankAccount $bank): array => [
                                $bank->getKey() => "{$bank->bank_name} — {$bank->maskedAccountNumber()}",
                            ])->all())
                        ->searchable()
                        ->required(),
                    Select::make('bank_statement_id')
                        ->label('Imported Statement')
                        ->options(fn (): array => BankStatement::query()
                            ->whereBelongsTo(Filament::getTenant())
                            ->whereIn('status', [BankStatementStatus::Imported, BankStatementStatus::Locked])
                            ->doesntHave('reconciliation')->orderByDesc('period_end')->get()
                            ->mapWithKeys(fn (BankStatement $statement): array => [
                                $statement->getKey() => $statement->period_start->toDateString().' to '.$statement->period_end->toDateString(),
                            ])->all())
                        ->searchable()
                        ->required(),
                    DatePicker::make('period_start')
                        ->label('Period Start Date')
                        ->required(),
                    DatePicker::make('period_end')
                        ->label('Period End Date')
                        ->afterOrEqual('period_start')
                        ->required(),
                ]),
        ]);
    }
}
