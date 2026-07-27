<?php

namespace App\Filament\Resources\Documents\Actions;

use App\Models\Document;
use App\Models\DocumentVersion;
use Filament\Actions\Action;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentFileActions
{
    public static function downloadCurrent(): Action
    {
        return Action::make('download')
            ->label('Download')
            ->icon('heroicon-o-arrow-down-tray')
            ->authorize('download')
            ->action(function (Document $record) {
                Gate::authorize('download', $record);

                return self::downloadVersion($record, $record->currentVersion);
            });
    }

    public static function previewCurrent(): Action
    {
        return Action::make('preview')
            ->label('Preview')
            ->icon('heroicon-o-eye')
            ->authorize('preview')
            ->action(function (Document $record) {
                Gate::authorize('preview', $record);

                return self::previewVersion($record, $record->currentVersion);
            });
    }

    public static function downloadHistoricalVersion(): Action
    {
        return Action::make('downloadVersion')
            ->label('Download')
            ->icon('heroicon-o-arrow-down-tray')
            ->authorize(
                fn (DocumentVersion $record): bool => Gate::allows('download', $record->document),
            )
            ->action(function (DocumentVersion $record) {
                Gate::authorize('download', $record->document);

                return self::downloadVersion($record->document, $record);
            });
    }

    public static function previewHistoricalVersion(): Action
    {
        return Action::make('previewVersion')
            ->label('Preview')
            ->icon('heroicon-o-eye')
            ->authorize(
                fn (DocumentVersion $record): bool => Gate::allows('preview', $record->document),
            )
            ->action(function (DocumentVersion $record) {
                Gate::authorize('preview', $record->document);

                return self::previewVersion($record->document, $record);
            });
    }

    private static function downloadVersion(
        Document $document,
        ?DocumentVersion $version,
    ): StreamedResponse {
        $version = self::requireExistingVersion($version);

        self::logFileAccess('downloaded', $document, $version);

        return Storage::disk($version->disk)->download(
            $version->path,
            $version->original_file_name,
        );
    }

    private static function previewVersion(
        Document $document,
        ?DocumentVersion $version,
    ): RedirectResponse {
        $version = self::requireExistingVersion($version);

        if (
            $version->mime_type !== 'application/pdf'
            && ! Str::startsWith($version->mime_type, 'image/')
        ) {
            throw ValidationException::withMessages([
                'document' => 'Preview is available only for PDF and image files. Download this file instead.',
            ]);
        }

        self::logFileAccess('previewed', $document, $version);

        return redirect()->away(
            Storage::disk($version->disk)->temporaryUrl(
                $version->path,
                now()->addMinutes(5),
            ),
        );
    }

    private static function requireExistingVersion(?DocumentVersion $version): DocumentVersion
    {
        if ($version === null || ! Storage::disk($version->disk)->exists($version->path)) {
            throw ValidationException::withMessages([
                'document' => 'The document file is unavailable.',
            ]);
        }

        return $version;
    }

    private static function logFileAccess(
        string $event,
        Document $document,
        DocumentVersion $version,
    ): void {
        activity('document_access')
            ->causedBy(auth()->user())
            ->performedOn($document)
            ->event($event)
            ->withProperties([
                'company_id' => $document->company_id,
                'document_version_id' => $version->getKey(),
                'version' => $version->version,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("{$event} document version {$version->version}");
    }
}
