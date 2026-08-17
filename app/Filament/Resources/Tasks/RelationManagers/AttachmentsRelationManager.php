<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Enums\AttachmentType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Deliverables & Files';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('attachment_type')
                    ->label('Type')
                    ->options(collect(AttachmentType::cases())->mapWithKeys(fn (AttachmentType $a) => [$a->value => $a->label()]))
                    ->default(AttachmentType::FileUpload->value)
                    ->live()
                    ->required(),
                TextInput::make('title')
                    ->label('Title / Label')
                    ->placeholder('e.g. Final Banner v1, Figma UI Prototype')
                    ->required(),
                FileUpload::make('file_path')
                    ->label('Upload File')
                    ->disk('public')
                    ->directory('task-attachments')
                    ->visible(fn (callable $get) => $get('attachment_type') === AttachmentType::FileUpload->value),
                TextInput::make('external_url')
                    ->label('External Deliverable URL')
                    ->placeholder('https://drive.google.com/... or https://figma.com/...')
                    ->url()
                    ->visible(fn (callable $get) => $get('attachment_type') !== AttachmentType::FileUpload->value),
                TextInput::make('version_number')
                    ->label('Version')
                    ->numeric()
                    ->default(1),
                Toggle::make('is_final_deliverable')
                    ->label('Mark as Final Approved Deliverable')
                    ->default(false),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Title')->searchable(),
                TextColumn::make('attachment_type')->badge()->label('Type')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                TextColumn::make('version_number')->badge()->label('Ver'),
                TextColumn::make('external_url')->label('URL / File')
                    ->formatStateUsing(fn ($record) => $record->external_url ? 'Open Link ↗' : ($record->file_path ? 'Download ⬇' : '—'))
                    ->url(fn ($record) => $record->external_url ?? ($record->file_path ? asset('storage/'.$record->file_path) : null), true),
                TextColumn::make('uploader.name')->label('Uploaded By')->placeholder('—'),
                IconColumn::make('is_final_deliverable')->boolean()->label('Final'),
                TextColumn::make('created_at')->dateTime()->label('Uploaded At'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Deliverable / Link')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['uploaded_by_user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
