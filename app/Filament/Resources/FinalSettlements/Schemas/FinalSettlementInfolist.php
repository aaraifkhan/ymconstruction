<?php

namespace App\Filament\Resources\FinalSettlements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FinalSettlementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference_number'),
                TextEntry::make('employment.employee.full_name')->label('Employee'),
                TextEntry::make('employment.employee_code')->label('Employee code'),
                TextEntry::make('cutoff_date')->date(),
                TextEntry::make('status')->badge(),
                TextEntry::make('earning_total')->money('PKR')->visible(fn ($record): bool => auth()->user()->can('viewAmounts', $record)),
                TextEntry::make('recovery_total')->money('PKR')->visible(fn ($record): bool => auth()->user()->can('viewAmounts', $record)),
                TextEntry::make('net_amount')->money('PKR')->visible(fn ($record): bool => auth()->user()->can('viewAmounts', $record)),
                TextEntry::make('balance_direction')->badge(),
                TextEntry::make('journalEntry.voucher_number')->label('GL voucher'),
                TextEntry::make('submitted_at')->dateTime(),
                TextEntry::make('reviewed_at')->dateTime(),
                TextEntry::make('approved_at')->dateTime(),
                TextEntry::make('posted_at')->dateTime(),
            ]);
    }
}
