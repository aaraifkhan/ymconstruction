<?php

namespace App\Filament\Resources\AttendanceImportBatches\Pages;

use App\Actions\HR\ImportAttendanceCsvAction;
use App\Filament\Resources\AttendanceImportBatches\AttendanceImportBatchResource;
use App\Models\AttendanceImportBatch;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceImportBatches extends ListRecords
{
    protected static string $resource = AttendanceImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importCsv')
                ->label('Import machine CSV')
                ->authorize(fn (): bool => auth()->user()?->can('import', [
                    AttendanceImportBatch::class,
                    Filament::getTenant(),
                ]) ?? false)
                ->schema([
                    FileUpload::make('file')
                        ->label('Attendance CSV')
                        ->disk('local')
                        ->directory('private/attendance-imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(10240)
                        ->storeFileNamesIn('original_filename')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $batch = app(ImportAttendanceCsvAction::class)->handle(
                        Filament::getTenant(),
                        $data['file'],
                        $data['original_filename'] ?? basename($data['file']),
                        auth()->user(),
                    );

                    Notification::make()
                        ->title("Import {$batch->status->value}")
                        ->body("Accepted: {$batch->accepted_count}; duplicates: {$batch->duplicate_count}; quarantined: {$batch->quarantined_count}; errors: {$batch->error_count}.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
