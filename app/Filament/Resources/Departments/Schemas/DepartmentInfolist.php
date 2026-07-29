<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Department')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('code')->badge(),
                        TextEntry::make('parentDepartment.name')->label('Parent department')->placeholder('—'),
                        IconEntry::make('is_active')->boolean(),
                        TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
