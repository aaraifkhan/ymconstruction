<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->weight('bold')
                                    ->size('lg'),
                                TextEntry::make('email')
                                    ->icon('heroicon-m-envelope'),
                                TextEntry::make('roles.name')
                                    ->badge()
                                    ->separator(',')
                                    ->color('primary'),
                                TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->dateTime()
                                    ->icon('heroicon-m-check-badge'),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }
}
