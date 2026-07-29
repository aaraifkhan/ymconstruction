<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Models\LeaveRequest;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeaveRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('employment.id')
                    ->label('Employment'),
                TextEntry::make('leaveType.name')
                    ->label('Leave type'),
                TextEntry::make('leavePolicy.name')
                    ->label('Leave policy')
                    ->placeholder('-'),
                TextEntry::make('starts_on')
                    ->date(),
                TextEntry::make('ends_on')
                    ->date(),
                TextEntry::make('requested_units')
                    ->numeric(),
                TextEntry::make('reason')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge(),
                IconEntry::make('is_paid_snapshot')
                    ->boolean(),
                TextEntry::make('payroll_impact_snapshot')
                    ->badge(),
                TextEntry::make('requestedBy.name')
                    ->label('Requested by')
                    ->placeholder('-'),
                TextEntry::make('requested_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('managerDecidedBy.name')
                    ->label('Manager decided by')
                    ->placeholder('-'),
                TextEntry::make('manager_decided_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('hrDecidedBy.name')
                    ->label('Hr decided by')
                    ->placeholder('-'),
                TextEntry::make('hr_decided_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('decision_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('cancelledBy.name')
                    ->label('Cancelled by')
                    ->placeholder('-'),
                TextEntry::make('cancelled_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (LeaveRequest $record): bool => $record->trashed()),
            ]);
    }
}
