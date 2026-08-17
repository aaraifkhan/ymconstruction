<?php

namespace App\Filament\Resources\DepartmentTeams\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartmentTeamInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Team Overview')
                    ->schema([
                        TextEntry::make('name')->label('Team Name'),
                        TextEntry::make('code')->badge()->label('Team Code')->placeholder('—'),
                        TextEntry::make('department.name')->label('Department'),
                        TextEntry::make('team_type')->badge()->label('Team Type')
                            ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                        TextEntry::make('teamLead.employee.full_name')->label('Team Lead')->placeholder('Not Assigned'),
                        IconEntry::make('is_active')->boolean()->label('Active'),
                        TextEntry::make('description')->label('Description')->columnSpanFull()->placeholder('No description provided.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
