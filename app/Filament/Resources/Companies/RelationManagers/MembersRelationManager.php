<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Models\Company;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Company
            && Gate::allows('manageMembers', $ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('is_active')
                    ->label('Company access is active')
                    ->default(true),
                Toggle::make('can_access_descendants')
                    ->label('Can access sub-companies')
                    ->helperText('Also grants access to active companies below this company in the hierarchy.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active access')
                    ->boolean(),
                IconColumn::make('can_access_descendants')
                    ->label('Sub-company access')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->authorize('manageMembers', $this->getOwnerRecord())
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Toggle::make('is_active')
                            ->label('Company access is active')
                            ->default(true),
                        Toggle::make('can_access_descendants')
                            ->label('Can access sub-companies')
                            ->default(false),
                    ])
                    ->after(function (?Model $record): void {
                        if (! $record instanceof User) {
                            return;
                        }

                        activity('company_members')
                            ->performedOn($this->getOwnerRecord())
                            ->causedBy(auth()->user())
                            ->withProperties(['user_id' => $record->getKey()])
                            ->event('member_attached')
                            ->log('User granted company access');
                    }),
            ])
            ->recordActions([
                Action::make('updateCompanyAccess')
                    ->label('Update access')
                    ->icon('heroicon-o-shield-check')
                    ->authorize('manageMembers', $this->getOwnerRecord())
                    ->fillForm(fn (User $record): array => [
                        'is_active' => (bool) $record->pivot->is_active,
                        'can_access_descendants' => (bool) $record->pivot->can_access_descendants,
                    ])
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Company access is active'),
                        Toggle::make('can_access_descendants')
                            ->label('Can access sub-companies'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $this->getOwnerRecord()
                            ->members()
                            ->updateExistingPivot($record->getKey(), $data);

                        activity('company_members')
                            ->performedOn($this->getOwnerRecord())
                            ->causedBy(auth()->user())
                            ->withProperties([
                                'user_id' => $record->getKey(),
                                'is_active' => (bool) $data['is_active'],
                                'can_access_descendants' => (bool) $data['can_access_descendants'],
                            ])
                            ->event('member_access_updated')
                            ->log('Company access updated');
                    }),
                DetachAction::make()
                    ->authorize('manageMembers', $this->getOwnerRecord())
                    ->after(function (User $record): void {
                        activity('company_members')
                            ->performedOn($this->getOwnerRecord())
                            ->causedBy(auth()->user())
                            ->withProperties(['user_id' => $record->getKey()])
                            ->event('member_detached')
                            ->log('User company access removed');
                    }),
            ]);
    }
}
