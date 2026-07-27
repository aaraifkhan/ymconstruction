<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class BankAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'bankAccounts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Employee
            && Gate::allows('view', $ownerRecord)
            && Gate::allows('viewAny', EmployeeBankAccount::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('bank_name')->label('Bank name')->required()->maxLength(255),
            TextInput::make('branch_name')->label('Branch')->maxLength(255),
            TextInput::make('branch_code')->label('Branch code')->maxLength(50),
            TextInput::make('account_title')->label('Account title')->required()->maxLength(255),
            TextInput::make('account_number')
                ->label('Account number')
                ->maxLength(100)
                ->visible(fn (string $operation, ?EmployeeBankAccount $record): bool => $operation === 'create'
                    || ($record !== null && Gate::allows('viewSensitive', $record))),
            TextInput::make('iban')
                ->label('IBAN')
                ->maxLength(100)
                ->visible(fn (string $operation, ?EmployeeBankAccount $record): bool => $operation === 'create'
                    || ($record !== null && Gate::allows('viewSensitive', $record))),
            Toggle::make('is_primary_for_payroll')->label('Primary payroll account'),
            Toggle::make('is_active')->label('Active')->default(true),
            Textarea::make('notes')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bank_name')
            ->defaultSort('is_primary_for_payroll', 'desc')
            ->columns([
                TextColumn::make('bank_name')->label('Bank')->searchable()->sortable(),
                TextColumn::make('branch_name')->label('Branch')->placeholder('—'),
                TextColumn::make('account_title')->label('Account title')->searchable(),
                TextColumn::make('account_number')
                    ->label('Account number')
                    ->formatStateUsing(fn (?string $state, EmployeeBankAccount $record): string => Gate::allows('viewSensitive', $record)
                        ? ($state ?? '—')
                        : ($record->maskedAccountNumber() ?? '—')),
                TextColumn::make('iban')
                    ->label('IBAN')
                    ->formatStateUsing(fn (?string $state, EmployeeBankAccount $record): string => Gate::allows('viewSensitive', $record)
                        ? ($state ?? '—')
                        : ($record->maskedIban() ?? '—')),
                IconColumn::make('is_primary_for_payroll')->label('Payroll primary')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([TrashedFilter::make()])
            ->headerActions([
                CreateAction::make()->authorize(fn (): bool => Gate::allows('create', EmployeeBankAccount::class)),
            ])
            ->recordActions([
                EditAction::make()->authorize('update'),
                DeleteAction::make()->authorize('delete'),
                RestoreAction::make()->authorize('restore'),
            ]);
    }
}
