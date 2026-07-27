<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Models\Employee;
use App\Models\EmployeeQualification;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class QualificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'qualifications';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Employee
            && Gate::allows('view', $ownerRecord)
            && Gate::allows('viewAny', EmployeeQualification::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('qualification')->required()->maxLength(255),
            TextInput::make('institution')->maxLength(255),
            TextInput::make('field_of_study')->label('Field of study')->maxLength(255),
            TextInput::make('completion_year')
                ->label('Completion year')
                ->numeric()
                ->minValue(1950)
                ->maxValue((int) now()->format('Y') + 10),
            TextInput::make('grade')->maxLength(100),
            Textarea::make('notes')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('qualification')
            ->defaultSort('completion_year', 'desc')
            ->columns([
                TextColumn::make('qualification')->searchable()->sortable(),
                TextColumn::make('institution')->searchable()->placeholder('—'),
                TextColumn::make('field_of_study')->label('Field')->placeholder('—'),
                TextColumn::make('completion_year')->label('Year')->sortable()->placeholder('—'),
                TextColumn::make('grade')->placeholder('—'),
            ])
            ->filters([TrashedFilter::make()])
            ->headerActions([
                CreateAction::make()->authorize(fn (): bool => Gate::allows('create', EmployeeQualification::class)),
            ])
            ->recordActions([
                EditAction::make()->authorize('update'),
                DeleteAction::make()->authorize('delete'),
                RestoreAction::make()->authorize('restore'),
            ]);
    }
}
