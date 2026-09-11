<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveRequestStatus;
use App\Filament\Support\CompanyContextField;
use App\Models\Employment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Leave Request Information')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        CompanyContextField::make(),
                        Select::make('employment_id')
                            ->label('Employment')
                            ->options(fn (): array => Employment::query()
                                ->whereBelongsTo(Filament::getTenant())
                                ->with('employee')
                                ->get()
                                ->mapWithKeys(fn (Employment $employment): array => [
                                    $employment->getKey() => "{$employment->employee_code} — {$employment->employee?->full_name}",
                                ])
                                ->all())
                            ->searchable()
                            ->required(),
                        Select::make('leave_type_id')
                            ->relationship('leaveType', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->required(),
                        Select::make('leave_policy_id')
                            ->relationship('leavePolicy', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant()))
                            ->disabled(),
                        DatePicker::make('starts_on')
                            ->required(),
                        DatePicker::make('ends_on')
                            ->required(),
                        TextInput::make('requested_units')
                            ->label('Requested Units (Days / Hours)')
                            ->required()
                            ->numeric(),
                        Textarea::make('reason')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Workflow & Approval Details')
                    ->columnSpanFull()
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
                    ->schema([
                        Select::make('status')
                            ->options(LeaveRequestStatus::class)
                            ->default('draft')
                            ->disabled()
                            ->dehydrated(),
                        Toggle::make('is_paid_snapshot')
                            ->label('Is Paid Leave (Snapshot)')
                            ->disabled()
                            ->dehydrated(),
                        Select::make('payroll_impact_snapshot')
                            ->label('Payroll Impact')
                            ->options(LeavePayrollImpact::class)
                            ->disabled()
                            ->dehydrated(),
                        Select::make('requested_by_id')
                            ->relationship('requestedBy', 'name')
                            ->disabled(),
                        DateTimePicker::make('requested_at')->disabled(),
                        Select::make('manager_decided_by_id')
                            ->relationship('managerDecidedBy', 'name')
                            ->disabled(),
                        DateTimePicker::make('manager_decided_at')->disabled(),
                        Select::make('hr_decided_by_id')
                            ->relationship('hrDecidedBy', 'name')
                            ->disabled(),
                        DateTimePicker::make('hr_decided_at')->disabled(),
                        Select::make('cancelled_by_id')
                            ->relationship('cancelledBy', 'name')
                            ->disabled(),
                        DateTimePicker::make('cancelled_at')->disabled(),
                        Textarea::make('decision_reason')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
