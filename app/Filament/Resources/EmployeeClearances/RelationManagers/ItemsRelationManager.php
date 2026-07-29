<?php

namespace App\Filament\Resources\EmployeeClearances\RelationManagers;

use App\Actions\HR\ManageEmployeeClearanceAction;
use App\Enums\EmployeeClearanceItemStatus;
use App\Models\EmployeeClearanceItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('source_kind')->badge(),
                TextColumn::make('area')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('is_mandatory')->boolean(),
                TextColumn::make('recovery_recommendation_amount')->money('PKR'),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('decide')
                    ->authorize(fn (EmployeeClearanceItem $record): bool => auth()->user()->can('decide', $record))
                    ->schema([
                        Select::make('decision')->options(EmployeeClearanceItemStatus::class)
                            ->disableOptionWhen(fn (string $value): bool => $value === EmployeeClearanceItemStatus::Pending->value)
                            ->required(),
                        Textarea::make('notes'),
                        TextInput::make('recovery_amount')->numeric()->minValue(0),
                        Textarea::make('recovery_notes'),
                    ])
                    ->action(fn (EmployeeClearanceItem $record, array $data) => app(ManageEmployeeClearanceAction::class)
                        ->decideItem(
                            $record,
                            EmployeeClearanceItemStatus::from($data['decision']),
                            $data['notes'] ?? null,
                            auth()->user(),
                            filled($data['recovery_amount'] ?? null) ? (string) $data['recovery_amount'] : null,
                            $data['recovery_notes'] ?? null,
                        )),
            ])
            ->toolbarActions([]);
    }
}
