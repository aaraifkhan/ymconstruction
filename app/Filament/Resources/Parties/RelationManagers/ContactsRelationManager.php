<?php

namespace App\Filament\Resources\Parties\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('designation')->maxLength(255),
            TextInput::make('email')->email()->maxLength(255),
            TextInput::make('phone')->tel()->maxLength(50),
            Toggle::make('is_primary')->label('Primary contact')->default(false),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('designation')->searchable()->placeholder('-'),
                TextColumn::make('email')->searchable()->placeholder('-'),
                TextColumn::make('phone')->searchable()->placeholder('-'),
                IconColumn::make('is_primary')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([TrashedFilter::make()])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['company_id'] = $this->getOwnerRecord()->company_id;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
