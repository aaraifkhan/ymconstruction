<?php

namespace App\Filament\Resources\EmployeeFinancings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EmployeeFinancingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference_number')->placeholder('Pending submission'),
                TextEntry::make('employment.employee.full_name')->label('Employee'),
                TextEntry::make('employment.employee_code')->label('Employee code'),
                TextEntry::make('type')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('request_date')->date(),
                TextEntry::make('purpose')->columnSpanFull(),
                TextEntry::make('principal_amount')->money('PKR'),
                TextEntry::make('finance_charge')->money('PKR'),
                TextEntry::make('total_repayable')->money('PKR'),
                TextEntry::make('outstanding')->state(fn ($record): string => $record->outstandingAmount())->money('PKR'),
                TextEntry::make('installment_count'),
                TextEntry::make('first_due_date')->date(),
                TextEntry::make('approvedBy.name')->label('Approved by')->placeholder('-'),
                TextEntry::make('disbursed_at')->dateTime()->placeholder('-'),
                TextEntry::make('settled_at')->dateTime()->placeholder('-'),
            ]);
    }
}
