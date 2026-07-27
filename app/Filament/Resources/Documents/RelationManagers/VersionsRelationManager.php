<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Filament\Resources\Documents\Actions\DocumentFileActions;
use App\Models\Document;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'File Version History';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Document
            && Gate::allows('view', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_file_name')
            ->defaultSort('version', 'desc')
            ->columns([
                TextColumn::make('version')
                    ->label('Version')
                    ->prefix('v')
                    ->sortable(),
                TextColumn::make('original_file_name')
                    ->label('Original file name')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->label('File type')
                    ->toggleable(),
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn (int $state): string => Number::fileSize($state)),
                TextColumn::make('checksum')
                    ->label('SHA-256')
                    ->limit(16)
                    ->tooltip(fn (string $state): string => $state)
                    ->copyable(),
                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded by')
                    ->placeholder('System'),
                TextColumn::make('created_at')
                    ->label('Uploaded at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notes')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->recordActions([
                DocumentFileActions::previewHistoricalVersion(),
                DocumentFileActions::downloadHistoricalVersion(),
            ]);
    }
}
