<?php

namespace App\Filament\Resources\LeaveLedgerEntries\Schemas;

use App\Enums\LeaveLedgerEntryType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeaveLedgerEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('employment_id')
                    ->relationship('employment', 'id')
                    ->required(),
                Select::make('leave_type_id')
                    ->relationship('leaveType', 'name')
                    ->required(),
                Select::make('entry_type')
                    ->options(LeaveLedgerEntryType::class)
                    ->required(),
                DatePicker::make('effective_on')
                    ->required(),
                TextInput::make('units')
                    ->required()
                    ->numeric(),
                TextInput::make('source_type'),
                TextInput::make('source_id')
                    ->numeric(),
                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                Select::make('recorded_by_id')
                    ->relationship('recordedBy', 'name'),
            ]);
    }
}
