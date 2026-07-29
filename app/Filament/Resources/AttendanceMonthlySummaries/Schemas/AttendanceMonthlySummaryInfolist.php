<?php

namespace App\Filament\Resources\AttendanceMonthlySummaries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceMonthlySummaryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('employment.id')
                    ->label('Employment'),
                TextEntry::make('period_start')
                    ->date(),
                TextEntry::make('period_end')
                    ->date(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('scheduled_days')
                    ->numeric(),
                TextEntry::make('present_days')
                    ->numeric(),
                TextEntry::make('absent_days')
                    ->numeric(),
                TextEntry::make('half_days')
                    ->numeric(),
                TextEntry::make('leave_days')
                    ->numeric(),
                TextEntry::make('late_minutes')
                    ->numeric(),
                TextEntry::make('overtime_minutes')
                    ->numeric(),
                TextEntry::make('unpaid_leave_units')
                    ->numeric(),
                TextEntry::make('source_checksum'),
                TextEntry::make('finalizedBy.name')
                    ->label('Finalized by')
                    ->placeholder('-'),
                TextEntry::make('finalized_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
