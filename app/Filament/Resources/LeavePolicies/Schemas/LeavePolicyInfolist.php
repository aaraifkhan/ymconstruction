<?php

namespace App\Filament\Resources\LeavePolicies\Schemas;

use App\Models\LeavePolicy;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeavePolicyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('leaveType.name')
                    ->label('Leave type'),
                TextEntry::make('name'),
                TextEntry::make('effective_from')
                    ->date(),
                TextEntry::make('effective_to')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('annual_units')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('maximum_carry_forward_units')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('allow_negative_balance')
                    ->boolean(),
                IconEntry::make('allow_encashment')
                    ->boolean(),
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
                    ->visible(fn (LeavePolicy $record): bool => $record->trashed()),
            ]);
    }
}
