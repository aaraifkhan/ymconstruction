<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Models\Employee;
use App\Models\EmployeeExperience;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ExperiencesRelationManager extends RelationManager
{
    protected static string $relationship = 'experiences';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Employee
            && Gate::allows('view', $ownerRecord)
            && Gate::allows('viewAny', EmployeeExperience::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('company_name')->label('Company name')->required()->maxLength(255),
            TextInput::make('designation')->required()->maxLength(255),
            DatePicker::make('start_date')->label('Start date'),
            DatePicker::make('end_date')->label('End date')->afterOrEqual('start_date'),
            TextInput::make('duration_text')
                ->label('Duration (as recorded)')
                ->helperText('Optional, for legacy forms such as “2 years 6 months”.')
                ->maxLength(255),
            Textarea::make('reason_for_leaving')->label('Reason for leaving')->rows(3)->columnSpanFull(),
            Textarea::make('notes')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('company_name')->label('Company')->searchable()->sortable(),
                TextColumn::make('designation')->searchable(),
                TextColumn::make('start_date')->date()->placeholder('—')->sortable(),
                TextColumn::make('end_date')->date()->placeholder('—')->sortable(),
                TextColumn::make('duration_text')->label('Duration')->placeholder('—'),
                TextColumn::make('reason_for_leaving')->label('Reason')->limit(40)->placeholder('—'),
            ])
            ->filters([TrashedFilter::make()])
            ->headerActions([
                CreateAction::make()->authorize(fn (): bool => Gate::allows('create', EmployeeExperience::class)),
            ])
            ->recordActions([
                EditAction::make()->authorize('update'),
                DeleteAction::make()->authorize('delete'),
                RestoreAction::make()->authorize('restore'),
            ]);
    }
}
