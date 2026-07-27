<?php

namespace App\Filament\Resources\CompanyBankAccounts\Tables;

use App\Enums\BankAccountType;
use App\Models\CompanyBankAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class CompanyBankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('bank_name')
            ->columns([
                TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('account_title')
                    ->label('Account title')
                    ->searchable(),
                TextColumn::make('account_number')
                    ->label('Account number')
                    ->formatStateUsing(
                        fn (?string $state, CompanyBankAccount $record): string => Gate::allows('viewSensitive', $record)
                            ? ($state ?? '—')
                            : ($record->maskedAccountNumber() ?? '—')
                    ),
                TextColumn::make('iban')
                    ->label('IBAN')
                    ->formatStateUsing(
                        fn (?string $state, CompanyBankAccount $record): string => Gate::allows('viewSensitive', $record)
                            ? ($state ?? '—')
                            : ($record->maskedIban() ?? '—')
                    )
                    ->toggleable(),
                TextColumn::make('currency_code')
                    ->label('Currency')
                    ->badge(),
                TextColumn::make('account_type')
                    ->label('Type')
                    ->formatStateUsing(fn (BankAccountType $state): string => $state->label()),
                IconColumn::make('is_default_for_payroll')
                    ->label('Payroll default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label('Account type')
                    ->options(
                        collect(BankAccountType::cases())
                            ->mapWithKeys(fn (BankAccountType $type): array => [
                                $type->value => $type->label(),
                            ])
                            ->all()
                    ),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active accounts')
                    ->falseLabel('Inactive accounts')
                    ->placeholder('All accounts'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
