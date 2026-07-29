<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use App\Models\LeaveType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeaveTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('unit')
                    ->badge(),
                IconEntry::make('is_paid')
                    ->boolean(),
                TextEntry::make('payroll_impact')
                    ->badge(),
                IconEntry::make('requires_attachment')
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
                    ->visible(fn (LeaveType $record): bool => $record->trashed()),
            ]);
    }
}
