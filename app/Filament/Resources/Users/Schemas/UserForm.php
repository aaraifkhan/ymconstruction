<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('System User Account Details')
                    ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('User Full Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Corporate Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('roles')
                            ->label('Assigned System Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->default(now()),
                        TextInput::make('password')
                            ->label('User Password')
                            ->password()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
