<?php

namespace App\Filament\Resources\HrDataMigrations\Pages;

use App\Actions\HR\PrepareHrDataMigrationAction;
use App\Enums\HrDataMigrationType;
use App\Filament\Resources\HrDataMigrations\HrDataMigrationResource;
use App\Models\HrDataMigration;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListHrDataMigrations extends ListRecords
{
    protected static string $resource = HrDataMigrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prepareMigration')
                ->label('Prepare HR migration')
                ->authorize(fn (): bool => auth()->user()?->can('create', HrDataMigration::class) ?? false)
                ->schema([
                    Select::make('type')
                        ->options(collect(HrDataMigrationType::cases())->mapWithKeys(
                            fn (HrDataMigrationType $type): array => [$type->value => $type->label()],
                        ))->required(),
                    FileUpload::make('file')
                        ->label('Normalized CSV')
                        ->disk('local')
                        ->directory('private/hr-migration-uploads')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(10240)
                        ->storeFileNamesIn('original_filename')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $csv = Storage::disk('local')->get($data['file']);
                    $migration = app(PrepareHrDataMigrationAction::class)->handle(
                        Filament::getTenant(),
                        HrDataMigrationType::from($data['type']),
                        auth()->user(),
                        $data['original_filename'] ?? basename($data['file']),
                        $csv,
                    );
                    Storage::disk('local')->delete($data['file']);

                    Notification::make()
                        ->title('HR migration dry run prepared')
                        ->body("Valid rows: {$migration->valid_row_count} of {$migration->row_count}.")
                        ->success()->send();
                }),
        ];
    }
}
