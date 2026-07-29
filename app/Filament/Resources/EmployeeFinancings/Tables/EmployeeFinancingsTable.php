<?php

namespace App\Filament\Resources\EmployeeFinancings\Tables;

use App\Actions\HR\ApproveEmployeeFinancingAction;
use App\Actions\HR\CancelEmployeeFinancingAction;
use App\Actions\HR\CreateEmployeeFinancingDisbursementAction;
use App\Actions\HR\CreateEmployeeFinancingRecoveryAction;
use App\Actions\HR\RejectEmployeeFinancingAction;
use App\Actions\HR\RescheduleEmployeeFinancingAction;
use App\Actions\HR\SubmitEmployeeFinancingAction;
use App\Actions\HR\WaiveEmployeeFinancingAction;
use App\Enums\EmployeeFinancingStatus;
use App\Models\Account;
use App\Models\CompanyBankAccount;
use App\Models\EmployeeFinancing;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeeFinancingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('reference_number')->searchable()->placeholder('Pending'),
                TextColumn::make('employment.employee_code')->label('Employee code')->searchable(),
                TextColumn::make('employment.employee.full_name')->label('Employee')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('principal_amount')->money('PKR')->sortable(),
                TextColumn::make('total_repayable')->money('PKR')->sortable(),
                TextColumn::make('outstanding')->state(fn (EmployeeFinancing $record): string => $record->outstandingAmount())->money('PKR'),
                TextColumn::make('request_date')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EmployeeFinancingStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (EmployeeFinancing $record): bool => in_array($record->status, [
                    EmployeeFinancingStatus::Draft, EmployeeFinancingStatus::Rejected,
                ], true)),
                DeleteAction::make(),
                Action::make('submit')->authorize(fn (EmployeeFinancing $record): bool => auth()->user()->can('submit', $record))
                    ->visible(fn (EmployeeFinancing $record): bool => in_array($record->status, [EmployeeFinancingStatus::Draft, EmployeeFinancingStatus::Rejected], true))
                    ->requiresConfirmation()->action(fn (EmployeeFinancing $record) => app(SubmitEmployeeFinancingAction::class)->handle($record, auth()->user())),
                Action::make('approve')->authorize(fn (EmployeeFinancing $record): bool => auth()->user()->can('approve', $record))
                    ->visible(fn (EmployeeFinancing $record): bool => $record->status === EmployeeFinancingStatus::Requested)
                    ->requiresConfirmation()->action(fn (EmployeeFinancing $record) => app(ApproveEmployeeFinancingAction::class)->handle($record, auth()->user())),
                Action::make('reject')->color('danger')
                    ->authorize(fn (EmployeeFinancing $record): bool => auth()->user()->can('reject', $record))
                    ->visible(fn (EmployeeFinancing $record): bool => $record->status === EmployeeFinancingStatus::Requested)
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (EmployeeFinancing $record, array $data) => app(RejectEmployeeFinancingAction::class)->handle($record, auth()->user(), $data['reason'])),
                Action::make('disburse')->authorize(fn (EmployeeFinancing $record): bool => auth()->user()->can('disburse', $record))
                    ->visible(fn (EmployeeFinancing $record): bool => $record->status === EmployeeFinancingStatus::Approved)
                    ->schema(self::treasurySchema('source_account_id'))
                    ->action(function (EmployeeFinancing $record, array $data): void {
                        $transaction = app(CreateEmployeeFinancingDisbursementAction::class)->handle(
                            $record,
                            Account::query()->whereBelongsTo(Filament::getTenant())->findOrFail($data['source_account_id']),
                            filled($data['company_bank_account_id']) ? CompanyBankAccount::query()->whereBelongsTo(Filament::getTenant())->findOrFail($data['company_bank_account_id']) : null,
                            CarbonImmutable::parse($data['transaction_date']),
                            auth()->user(),
                        );
                        Notification::make()->title("Treasury draft #{$transaction->getKey()} created")->success()->send();
                    }),
                Action::make('recover')->authorize(fn (EmployeeFinancing $record): bool => auth()->user()->can('recover', $record))
                    ->visible(fn (EmployeeFinancing $record): bool => $record->status === EmployeeFinancingStatus::Active)
                    ->schema([TextInput::make('amount')->numeric()->minValue(0.0001)->required(), ...self::treasurySchema('destination_account_id')])
                    ->action(function (EmployeeFinancing $record, array $data): void {
                        $transaction = app(CreateEmployeeFinancingRecoveryAction::class)->handle(
                            $record,
                            (string) $data['amount'],
                            Account::query()->whereBelongsTo(Filament::getTenant())->findOrFail($data['destination_account_id']),
                            filled($data['company_bank_account_id']) ? CompanyBankAccount::query()->whereBelongsTo(Filament::getTenant())->findOrFail($data['company_bank_account_id']) : null,
                            CarbonImmutable::parse($data['transaction_date']),
                            auth()->user(),
                        );
                        Notification::make()->title("Treasury recovery draft #{$transaction->getKey()} created")->success()->send();
                    }),
                Action::make('reschedule')->authorize(fn (EmployeeFinancing $record): bool => auth()->user()->can('reschedule', $record))
                    ->visible(fn (EmployeeFinancing $record): bool => $record->status === EmployeeFinancingStatus::Active)
                    ->schema([
                        TextInput::make('installment_count')->integer()->minValue(1)->required(),
                        DatePicker::make('first_due_date')->required(),
                        Textarea::make('reason')->required(),
                    ])->action(fn (EmployeeFinancing $record, array $data) => app(RescheduleEmployeeFinancingAction::class)->handle(
                        $record, (int) $data['installment_count'], CarbonImmutable::parse($data['first_due_date']), $data['reason'], auth()->user(),
                    )),
                Action::make('waive')->color('warning')->authorize(fn (EmployeeFinancing $record): bool => auth()->user()->can('waive', $record))
                    ->visible(fn (EmployeeFinancing $record): bool => $record->status === EmployeeFinancingStatus::Active)
                    ->schema([
                        TextInput::make('amount')->numeric()->minValue(0.0001)->required(),
                        Select::make('expense_account_id')->label('Waiver expense account')
                            ->options(fn (): array => Account::query()->whereBelongsTo(Filament::getTenant())
                                ->where('account_type', 'expense')->where('is_active', true)
                                ->whereDoesntHave('children')->orderBy('code')->get()
                                ->mapWithKeys(fn (Account $account): array => [
                                    $account->getKey() => "{$account->code} — {$account->name}",
                                ])->all())->searchable()->required(),
                        DatePicker::make('effective_date')->default(now())->required(),
                        Textarea::make('reason')->required(),
                    ])->action(fn (EmployeeFinancing $record, array $data) => app(WaiveEmployeeFinancingAction::class)->handle(
                        $record,
                        (string) $data['amount'],
                        Account::query()->whereBelongsTo(Filament::getTenant())->findOrFail($data['expense_account_id']),
                        CarbonImmutable::parse($data['effective_date']),
                        $data['reason'],
                        auth()->user(),
                    )),
                Action::make('cancel')->color('danger')->authorize(fn (EmployeeFinancing $record): bool => auth()->user()->can('cancel', $record))
                    ->visible(fn (EmployeeFinancing $record): bool => in_array($record->status, [
                        EmployeeFinancingStatus::Draft, EmployeeFinancingStatus::Rejected, EmployeeFinancingStatus::Approved,
                    ], true))
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (EmployeeFinancing $record, array $data) => app(CancelEmployeeFinancingAction::class)->handle($record, $data['reason'], auth()->user())),
            ]);
    }

    /** @return array<int, mixed> */
    private static function treasurySchema(string $accountField): array
    {
        return [
            Select::make($accountField)->label('Cash / bank GL account')
                ->options(fn (): array => Account::query()->whereBelongsTo(Filament::getTenant())
                    ->where('is_active', true)->where('allows_manual_posting', true)
                    ->whereHas('accountingMappings', fn ($query) => $query->where('is_active', true))
                    ->orderBy('code')->get()->mapWithKeys(fn (Account $account): array => [
                        $account->getKey() => "{$account->code} — {$account->name}",
                    ])->all())->searchable()->required(),
            Select::make('company_bank_account_id')->label('Physical bank account')
                ->options(fn (): array => CompanyBankAccount::query()->whereBelongsTo(Filament::getTenant())
                    ->orderBy('bank_name')->get()->mapWithKeys(fn (CompanyBankAccount $account): array => [
                        $account->getKey() => "{$account->bank_name} — {$account->account_title}",
                    ])->all())->searchable(),
            DatePicker::make('transaction_date')->default(now())->required(),
        ];
    }
}
