<?php

namespace App\Filament\Resources\HrDataMigrations\Pages;

use App\Actions\HR\ImportHrDataMigrationAction;
use App\Actions\HR\RollbackHrDataMigrationAction;
use App\Actions\HR\ValidateHrDataMigrationAction;
use App\Enums\HrDataMigrationStatus;
use App\Filament\Resources\HrDataMigrations\HrDataMigrationResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewHrDataMigration extends ViewRecord
{
    protected static string $resource = HrDataMigrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('validate')
                ->label('Validate dry run')
                ->authorize('validate')
                ->visible(fn (): bool => $this->record->status === HrDataMigrationStatus::Draft)
                ->requiresConfirmation()
                ->action(fn () => app(ValidateHrDataMigrationAction::class)->handle($this->record, auth()->user())),
            Action::make('import')
                ->label('Import approved source')
                ->authorize('import')
                ->visible(fn (): bool => $this->record->status === HrDataMigrationStatus::Validated)
                ->requiresConfirmation()
                ->action(fn () => app(ImportHrDataMigrationAction::class)->handle($this->record, auth()->user())),
            Action::make('rollback')
                ->label('Rollback import')
                ->color('danger')
                ->authorize('rollback')
                ->visible(fn (): bool => $this->record->status === HrDataMigrationStatus::Imported)
                ->schema([
                    Textarea::make('reason')->required()->minLength(10)->maxLength(2000),
                ])
                ->action(fn (array $data) => app(RollbackHrDataMigrationAction::class)
                    ->handle($this->record, auth()->user(), $data['reason'])),
        ];
    }
}
