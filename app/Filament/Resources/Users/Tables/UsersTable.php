<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make(),
                \Filament\Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verification')
                    ->nullable()
                    ->placeholder('All users')
                    ->trueLabel('Verified')
                    ->falseLabel('Unverified'),
                \Filament\Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\DeleteAction::make(),
                    \Filament\Actions\RestoreAction::make(),
                    \Filament\Actions\ForceDeleteAction::make()
                        ->modalDescription('Are you sure you want to permanently delete this user? This action cannot be undone.'),
                    \Filament\Actions\Action::make('resetPassword')
                        ->label('Reset Password')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->visible(fn () => auth()->user()->can('ResetPassword:User'))
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\TextInput::make('password')
                                ->label('New Password')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->confirmed(),
                            \Filament\Forms\Components\TextInput::make('password_confirmation')
                                ->label('Confirm New Password')
                                ->password()
                                ->required()
                        ])
                        ->action(function ($record, array $data): void {
                            $record->update([
                                'password' => \Illuminate\Support\Facades\Hash::make($data['password'])
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Password Reset')
                                ->body('The password for this user has been successfully reset.')
                                ->success()
                                ->send();
                        })
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
