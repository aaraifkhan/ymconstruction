<?php

namespace App\Filament\Resources\AttendanceRules\Schemas;

use App\Models\AttendanceRule;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceRuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('name'),
                TextEntry::make('effective_from')
                    ->date(),
                TextEntry::make('effective_to')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('grace_minutes')
                    ->numeric(),
                TextEntry::make('late_rounding_minutes')
                    ->numeric(),
                TextEntry::make('half_day_after_minutes')
                    ->numeric(),
                TextEntry::make('absence_after_minutes')
                    ->numeric(),
                TextEntry::make('minimum_overtime_minutes')
                    ->numeric(),
                TextEntry::make('missing_punch_treatment')
                    ->badge(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (AttendanceRule $record): bool => $record->trashed()),
            ]);
    }
}
