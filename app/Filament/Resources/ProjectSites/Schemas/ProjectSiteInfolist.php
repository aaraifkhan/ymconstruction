<?php

namespace App\Filament\Resources\ProjectSites\Schemas;

use App\Models\ProjectSite;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProjectSiteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('project.name')
                    ->label('Project'),
                TextEntry::make('costCenter.name')
                    ->label('Cost center')
                    ->placeholder('-'),
                TextEntry::make('code'),
                TextEntry::make('name'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('location')
                    ->placeholder('-')
                    ->columnSpanFull(),
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
                    ->visible(fn (ProjectSite $record): bool => $record->trashed()),
            ]);
    }
}
