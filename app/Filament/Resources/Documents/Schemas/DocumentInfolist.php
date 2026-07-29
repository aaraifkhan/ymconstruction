<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\DocumentClassification;
use App\Enums\DocumentStatus;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document')
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('reference_number')
                            ->label('Reference number')
                            ->placeholder('—'),
                        TextEntry::make('category.name')
                            ->label('Category')
                            ->badge(),
                        TextEntry::make('hrDocumentType.name')
                            ->label('HR document type')
                            ->badge()
                            ->placeholder('Legacy / free-form'),
                        TextEntry::make('classification')
                            ->label('Sensitivity')
                            ->formatStateUsing(
                                fn (DocumentClassification $state): string => $state->label(),
                            )
                            ->badge(),
                        TextEntry::make('status')
                            ->formatStateUsing(fn (DocumentStatus $state): string => $state->label())
                            ->badge()
                            ->color(fn (DocumentStatus $state): string => $state->color()),
                        TextEntry::make('issue_date')
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('expiry_date')
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        KeyValueEntry::make('metadata')
                            ->label('Additional metadata')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Current file')
                    ->schema([
                        TextEntry::make('currentVersion.version')
                            ->label('Version')
                            ->prefix('v'),
                        TextEntry::make('currentVersion.original_file_name')
                            ->label('Original file name'),
                        TextEntry::make('currentVersion.mime_type')
                            ->label('File type'),
                        TextEntry::make('currentVersion.size')
                            ->label('File size')
                            ->formatStateUsing(
                                fn (int $state): string => Number::fileSize($state),
                            ),
                        TextEntry::make('currentVersion.checksum')
                            ->label('SHA-256 checksum')
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('currentVersion.uploadedBy.name')
                            ->label('Uploaded by')
                            ->placeholder('System'),
                        TextEntry::make('currentVersion.created_at')
                            ->label('Uploaded at')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Review history')
                    ->schema([
                        TextEntry::make('verifiedBy.name')
                            ->label('Verified by')
                            ->placeholder('Not verified'),
                        TextEntry::make('verified_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('approvedBy.name')
                            ->label('Approved by')
                            ->placeholder('Not approved'),
                        TextEntry::make('approved_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('rejectedBy.name')
                            ->label('Rejected by')
                            ->placeholder('—'),
                        TextEntry::make('rejected_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('rejection_reason')
                            ->label('Rejection reason')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
