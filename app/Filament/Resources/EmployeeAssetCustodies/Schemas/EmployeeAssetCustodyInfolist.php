<?php

namespace App\Filament\Resources\EmployeeAssetCustodies\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EmployeeAssetCustodyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference_number')->placeholder('Draft'),
                TextEntry::make('fixedAsset.asset_number')->label('Asset'),
                TextEntry::make('fixedAsset.name')->label('Asset name'),
                TextEntry::make('employment.employee_code')->label('Employee code'),
                TextEntry::make('employment.employee.full_name')->label('Employee'),
                TextEntry::make('status')->badge(),
                TextEntry::make('issued_on')->date(),
                TextEntry::make('due_on')->date()->placeholder('No due date'),
                TextEntry::make('issued_condition'),
                TextEntry::make('issued_location'),
                TextEntry::make('accessories')->formatStateUsing(fn (?array $state): string => implode(', ', $state ?? [])),
                TextEntry::make('return_condition'),
                TextEntry::make('exception_type')->badge(),
                TextEntry::make('recovery_recommendation_amount')->money('PKR'),
            ]);
    }
}
