<?php

namespace App\Filament\Resources\PayrollVariableComponents\Tables;

use App\Actions\Payroll\ApprovePayrollVariableComponentAction;
use App\Actions\Payroll\RejectPayrollVariableComponentAction;
use App\Actions\Payroll\SubmitPayrollVariableComponentAction;
use App\Enums\PayrollVariableComponentStatus;
use App\Enums\PayrollVariableComponentType;
use App\Models\PayrollVariableComponent;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PayrollVariableComponentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_reference')->searchable(),
                TextColumn::make('employment.employee_code')->label('Employee code')->searchable(),
                TextColumn::make('employment.employee.full_name')->label('Employee')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('amount')->money('PKR'),
                TextColumn::make('earning_period_start')->date()->sortable(),
                TextColumn::make('earning_period_end')->date(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('type')->options(PayrollVariableComponentType::class),
                SelectFilter::make('status')->options(PayrollVariableComponentStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (PayrollVariableComponent $record): bool => in_array($record->status, [
                    PayrollVariableComponentStatus::Draft,
                    PayrollVariableComponentStatus::Rejected,
                ], true)),
                DeleteAction::make(),
                Action::make('submit')->requiresConfirmation()
                    ->authorize(fn (PayrollVariableComponent $record): bool => auth()->user()->can('submit', $record))
                    ->visible(fn (PayrollVariableComponent $record): bool => in_array($record->status, [
                        PayrollVariableComponentStatus::Draft,
                        PayrollVariableComponentStatus::Rejected,
                    ], true))
                    ->action(fn (PayrollVariableComponent $record) => app(SubmitPayrollVariableComponentAction::class)
                        ->handle($record, auth()->user())),
                Action::make('approve')->requiresConfirmation()
                    ->authorize(fn (PayrollVariableComponent $record): bool => auth()->user()->can('approve', $record))
                    ->visible(fn (PayrollVariableComponent $record): bool => $record->status === PayrollVariableComponentStatus::PendingApproval)
                    ->action(fn (PayrollVariableComponent $record) => app(ApprovePayrollVariableComponentAction::class)
                        ->handle($record, auth()->user())),
                Action::make('reject')->color('danger')
                    ->authorize(fn (PayrollVariableComponent $record): bool => auth()->user()->can('reject', $record))
                    ->visible(fn (PayrollVariableComponent $record): bool => $record->status === PayrollVariableComponentStatus::PendingApproval)
                    ->schema([Textarea::make('reason')->required()])
                    ->action(fn (PayrollVariableComponent $record, array $data) => app(RejectPayrollVariableComponentAction::class)
                        ->handle($record, auth()->user(), $data['reason'])),
            ]);
    }
}
