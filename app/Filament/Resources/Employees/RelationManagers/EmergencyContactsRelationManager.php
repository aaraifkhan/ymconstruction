<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EmergencyContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'emergencyContacts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Employee
            && Gate::allows('view', $ownerRecord)
            && Gate::allows('viewAny', EmployeeEmergencyContact::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('relationship')->required()->maxLength(255),
            TextInput::make('mobile')->tel()->required()->maxLength(50),
            Textarea::make('address')->rows(3)->columnSpanFull(),
            Toggle::make('is_primary')->label('Primary emergency contact'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('is_primary', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('relationship')->searchable(),
                TextColumn::make('mobile'),
                IconColumn::make('is_primary')->label('Primary')->boolean(),
            ])
            ->filters([TrashedFilter::make()])
            ->headerActions([
                CreateAction::make()->authorize(fn (): bool => Gate::allows('create', EmployeeEmergencyContact::class)),
            ])
            ->recordActions([
                EditAction::make()->authorize('update'),
                DeleteAction::make()->authorize('delete'),
                RestoreAction::make()->authorize('restore'),
            ]);
    }
}
