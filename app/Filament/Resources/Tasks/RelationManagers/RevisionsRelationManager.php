<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Revision History';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('revision_number')->badge()->label('Revision #'),
                TextColumn::make('requester.name')->label('Requested By'),
                TextColumn::make('requested_at')->dateTime()->label('Requested Date & Time'),
                TextColumn::make('revision_notes')->label('Revision Instructions')->wrap(),
                TextColumn::make('submitted_response')->label('Response / Resubmission')->wrap()->placeholder('Pending Fix'),
                TextColumn::make('resubmitted_at')->dateTime()->label('Resubmitted At')->placeholder('—'),
            ])
            ->defaultSort('revision_number', 'desc');
    }
}
