<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Enums\LeavePayrollImpact;
use App\Enums\LeaveRequestStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('employment_id')
                    ->relationship('employment', 'id')
                    ->required(),
                Select::make('leave_type_id')
                    ->relationship('leaveType', 'name')
                    ->required(),
                Select::make('leave_policy_id')
                    ->relationship('leavePolicy', 'name')
                    ->disabled(),
                DatePicker::make('starts_on')
                    ->required(),
                DatePicker::make('ends_on')
                    ->required(),
                TextInput::make('requested_units')
                    ->required()
                    ->numeric(),
                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(LeaveRequestStatus::class)
                    ->default('draft')
                    ->disabled()
                    ->dehydrated(),
                Toggle::make('is_paid_snapshot')
                    ->disabled()
                    ->dehydrated(),
                Select::make('payroll_impact_snapshot')
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
                Textarea::make('decision_reason')
                    ->disabled()
                    ->columnSpanFull(),
                Select::make('cancelled_by_id')
                    ->relationship('cancelledBy', 'name')
                    ->disabled(),
                DateTimePicker::make('cancelled_at')->disabled(),
            ]);
    }
}
