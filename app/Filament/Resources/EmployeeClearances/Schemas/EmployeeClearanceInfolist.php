<?php

namespace App\Filament\Resources\EmployeeClearances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EmployeeClearanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference_number'),
                TextEntry::make('employment.employee_code')->label('Employee code'),
                TextEntry::make('employment.employee.full_name')->label('Employee'),
                TextEntry::make('separation.type')->badge(),
                TextEntry::make('separation.approved_last_working_date')->date(),
                TextEntry::make('status')->badge(),
                TextEntry::make('submitted_at')->dateTime(),
                TextEntry::make('completed_at')->dateTime(),
            ]);
    }
}
