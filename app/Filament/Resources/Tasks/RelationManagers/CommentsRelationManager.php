<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments & Activity Stream';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('comment')
                    ->label('Comment / Update')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_blocker')
                    ->label('Mark as Blocker / Dependency Issue')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->weight('bold'),
                TextColumn::make('comment')->label('Comment / Update')->wrap(),
                IconColumn::make('is_blocker')->boolean()->label('Blocker'),
                TextColumn::make('created_at')->dateTime()->label('Time'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Comment / Note')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
