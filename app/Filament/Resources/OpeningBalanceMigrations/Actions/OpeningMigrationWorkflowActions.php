<?php

namespace App\Filament\Resources\OpeningBalanceMigrations\Actions;

use App\Actions\Accounting\ImportOpeningBalanceMigrationAction;
use App\Actions\Accounting\ReverseOpeningBalanceMigrationAction;
use App\Actions\Accounting\ValidateOpeningBalanceMigrationAction;
use App\Enums\OpeningBalanceMigrationStatus;
use App\Models\OpeningBalanceMigration;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

class OpeningMigrationWorkflowActions
{
    public static function validate(): Action
    {
        return Action::make('validate')->label('Validate dry run')->authorize('validate')
            ->visible(fn (OpeningBalanceMigration $record): bool => $record->status === OpeningBalanceMigrationStatus::Draft)
            ->requiresConfirmation()->action(fn (OpeningBalanceMigration $record) => app(ValidateOpeningBalanceMigrationAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function import(): Action
    {
        return Action::make('import')->label('Import approved source')->color('success')->authorize('import')
            ->visible(fn (OpeningBalanceMigration $record): bool => $record->status === OpeningBalanceMigrationStatus::Validated)
            ->requiresConfirmation()->action(fn (OpeningBalanceMigration $record) => app(ImportOpeningBalanceMigrationAction::class)->handle($record, Filament::auth()->user()));
    }

    public static function reverse(): Action
    {
        return Action::make('reverse')->label('Rollback')->color('danger')->authorize('reverse')
            ->visible(fn (OpeningBalanceMigration $record): bool => $record->status === OpeningBalanceMigrationStatus::Imported)
            ->schema([
                DatePicker::make('date')->default(today())->required(),
                Textarea::make('reason')->required()->maxLength(2000),
            ])->action(fn (OpeningBalanceMigration $record, array $data) => app(ReverseOpeningBalanceMigrationAction::class)
            ->handle($record, Filament::auth()->user(), Carbon::parse($data['date']), $data['reason']));
    }
}
