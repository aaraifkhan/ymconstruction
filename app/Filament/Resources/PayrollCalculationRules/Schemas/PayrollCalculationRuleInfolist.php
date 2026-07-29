<?php

namespace App\Filament\Resources\PayrollCalculationRules\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PayrollCalculationRuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('effective_from')->date(),
                TextEntry::make('effective_to')->date()->placeholder('Open ended'),
                TextEntry::make('requires_finalized_attendance')->boolean(),
                TextEntry::make('prorate_allowances')->boolean(),
                TextEntry::make('absence_day_factor'),
                TextEntry::make('unpaid_leave_day_factor'),
                TextEntry::make('half_day_factor'),
                TextEntry::make('deduct_late_minutes')->boolean(),
                TextEntry::make('standard_day_minutes'),
                TextEntry::make('is_active')->boolean(),
            ])->columns(2);
    }
}
