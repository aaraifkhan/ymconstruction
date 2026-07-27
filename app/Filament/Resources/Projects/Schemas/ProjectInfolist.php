<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('client_party_id')
                    ->numeric(),
                TextEntry::make('consultant_party_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('location')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('planned_start_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('planned_completion_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('actual_start_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('actual_completion_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('contract_value')
                    ->numeric(),
                TextEntry::make('retention_terms')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('mobilization_terms')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('currency_code'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Project $record): bool => $record->trashed()),
            ]);
    }
}
