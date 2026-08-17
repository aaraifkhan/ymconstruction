<?php

namespace App\Filament\Resources\DepartmentTeams\RelationManagers;

use App\Enums\TeamMemberRole;
use App\Models\Employment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Team Members';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employment_id')
                    ->label('Employee')
                    ->relationship(
                        'employment',
                        'employee_code',
                        fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant())
                    )
                    ->getOptionLabelFromRecordUsing(fn (Employment $record) => "{$record->employee?->full_name} ({$record->employee_code}) - {$record->designation?->name}")
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('role_in_team')
                    ->label('Role in Team')
                    ->options(collect(TeamMemberRole::cases())->mapWithKeys(fn (TeamMemberRole $r) => [$r->value => $r->label()]))
                    ->default(TeamMemberRole::Member->value)
                    ->required(),
                DatePicker::make('joined_at')
                    ->label('Joined Date')
                    ->default(now()),
                Toggle::make('is_active')
                    ->label('Active in Team')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employment.employee.full_name')->label('Member Name')->searchable(),
                TextColumn::make('employment.employee_code')->label('Emp Code')->badge(),
                TextColumn::make('employment.designation.name')->label('Designation')->placeholder('—'),
                TextColumn::make('role_in_team')->badge()->label('Role')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                TextColumn::make('joined_at')->date()->label('Joined At'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->headerActions([
                CreateAction::make()->label('Add Team Member'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
