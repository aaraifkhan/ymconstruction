<?php

namespace App\Filament\Resources\Documents\Actions;

use App\Actions\Documents\ApproveDocumentAction;
use App\Actions\Documents\RejectDocumentAction;
use App\Actions\Documents\UploadDocumentVersionAction;
use App\Actions\Documents\VerifyDocumentAction;
use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\Schemas\DocumentForm;
use App\Models\Document;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class DocumentWorkflowActions
{
    public static function uploadVersion(): Action
    {
        return Action::make('uploadVersion')
            ->label('Upload New Version')
            ->icon('heroicon-o-arrow-up-tray')
            ->authorize('uploadVersion')
            ->schema([
                FileUpload::make('uploaded_file_path')
                    ->label('Document file')
                    ->disk('local')
                    ->directory(
                        fn (Document $record): string => "documents/{$record->company_id}/incoming",
                    )
                    ->visibility('private')
                    ->storeFileNamesIn('original_file_name')
                    ->acceptedFileTypes(DocumentForm::acceptedFileTypes())
                    ->rules([
                        'extensions:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt',
                    ])
                    ->maxSize(10240)
                    ->downloadable(false)
                    ->openable(false)
                    ->previewable(false)
                    ->required(),
                Textarea::make('notes')
                    ->label('Version notes')
                    ->maxLength(2000)
                    ->rows(3),
            ])
            ->action(function (
                array $data,
                Document $record,
                UploadDocumentVersionAction $uploadVersion,
            ): void {
                Gate::authorize('uploadVersion', $record);

                $uploadVersion->handle(
                    document: $record,
                    uploadedFilePath: $data['uploaded_file_path'],
                    originalFileName: $data['original_file_name'],
                    actor: self::authenticatedUser(),
                    notes: $data['notes'] ?? null,
                );

                Notification::make()
                    ->title('New document version uploaded')
                    ->success()
                    ->send();
            });
    }

    public static function verify(): Action
    {
        return Action::make('verify')
            ->label('Verify')
            ->icon('heroicon-o-check-badge')
            ->color('info')
            ->authorize('verify')
            ->visible(
                fn (Document $record): bool => $record->category->requires_verification
                    && $record->status !== DocumentStatus::Approved,
            )
            ->requiresConfirmation()
            ->action(function (Document $record, VerifyDocumentAction $verify): void {
                Gate::authorize('verify', $record);
                $verify->handle($record, self::authenticatedUser());

                Notification::make()
                    ->title('Document verified')
                    ->success()
                    ->send();
            });
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-hand-thumb-up')
            ->color('success')
            ->authorize('approve')
            ->visible(
                fn (Document $record): bool => $record->category->requires_approval
                    && $record->status !== DocumentStatus::Approved,
            )
            ->requiresConfirmation()
            ->action(function (Document $record, ApproveDocumentAction $approve): void {
                Gate::authorize('approve', $record);
                $approve->handle($record, self::authenticatedUser());

                Notification::make()
                    ->title('Document approved')
                    ->success()
                    ->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->authorize('reject')
            ->visible(fn (Document $record): bool => $record->status !== DocumentStatus::Rejected)
            ->schema([
                Textarea::make('reason')
                    ->label('Rejection reason')
                    ->required()
                    ->maxLength(2000)
                    ->rows(4),
            ])
            ->action(function (
                array $data,
                Document $record,
                RejectDocumentAction $reject,
            ): void {
                Gate::authorize('reject', $record);
                $reject->handle($record, self::authenticatedUser(), $data['reason']);

                Notification::make()
                    ->title('Document rejected')
                    ->success()
                    ->send();
            });
    }

    private static function authenticatedUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
