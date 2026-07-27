<?php

namespace App\Filament\Resources\ProjectBudgets\Schemas;

use App\Models\ProjectBudget;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProjectBudgetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('project.name')
                    ->label('Project'),
                TextEntry::make('version')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('currency_code'),
                TextEntry::make('total_amount')
                    ->numeric(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('preparedBy.name')
                    ->label('Prepared by')
                    ->placeholder('-'),
                TextEntry::make('approvedBy.name')
                    ->label('Approved by')
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (ProjectBudget $record): bool => $record->trashed()),
            ]);
    }
}
