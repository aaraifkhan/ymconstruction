<?php

namespace App\Filament\Resources\CompanyHolidays\Schemas;

use App\Models\CompanyHoliday;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CompanyHolidayInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('workCalendar.name')
                    ->label('Work calendar'),
                TextEntry::make('name'),
                TextEntry::make('holiday_date')
                    ->date(),
                IconEntry::make('is_paid')
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
                    ->visible(fn (CompanyHoliday $record): bool => $record->trashed()),
            ]);
    }
}
