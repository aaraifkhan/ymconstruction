<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class UploadDocumentVersionAction
{
    private const array ALLOWED_EXTENSIONS = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'csv',
        'txt',
    ];

    private const array ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
    ];

    private const int MAX_FILE_SIZE = 10 * 1024 * 1024;

    public function handle(
        Document $document,
        string $uploadedFilePath,
        string $originalFileName,
        User $actor,
        ?string $notes = null,
        bool $authorize = true,
    ): DocumentVersion {
        if ($authorize) {
            Gate::forUser($actor)->authorize('uploadVersion', $document);
        }

        $validatedNotes = Validator::make(
            ['notes' => $notes],
            ['notes' => ['nullable', 'string', 'max:2000']],
        )->validate();
        $notes = $validatedNotes['notes'];

        $expectedDirectory = "documents/{$document->company_id}/incoming/";

        if (
            ! Str::startsWith($uploadedFilePath, $expectedDirectory)
            || ! Storage::disk('local')->exists($uploadedFilePath)
        ) {
            throw ValidationException::withMessages([
                'uploaded_file_path' => 'The uploaded document file is invalid.',
            ]);
        }

        $originalFileName = Str::limit(basename($originalFileName), 255, '');
        $mimeType = Storage::disk('local')->mimeType($uploadedFilePath);
        $size = Storage::disk('local')->size($uploadedFilePath);
        $extension = Str::lower(pathinfo($originalFileName, PATHINFO_EXTENSION));

        if (
            ! is_string($mimeType)
            || ! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)
            || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)
            || $size > self::MAX_FILE_SIZE
        ) {
            Storage::disk('local')->delete($uploadedFilePath);

            throw ValidationException::withMessages([
                'uploaded_file_path' => 'The uploaded file type, extension, or size is not allowed.',
            ]);
        }

        try {
            return DB::transaction(function () use (
                $actor,
                $document,
                $extension,
                $mimeType,
                $notes,
                $originalFileName,
                $size,
                $uploadedFilePath,
            ): DocumentVersion {
                $lockedDocument = Document::query()
                    ->whereKey($document)
                    ->lockForUpdate()
                    ->firstOrFail();

                $nextVersion = ((int) $lockedDocument->versions()->max('version')) + 1;

                $version = $lockedDocument->versions()->create([
                    'version' => $nextVersion,
                    'disk' => 'local',
                    'path' => $uploadedFilePath,
                    'original_file_name' => $originalFileName,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'size' => $size,
                    'checksum' => $this->sha256Checksum($uploadedFilePath),
                    'uploaded_by_id' => $actor->getKey(),
                    'notes' => $notes,
                ]);

                $lockedDocument->update([
                    'status' => DocumentStatus::Draft,
                    'verified_by_id' => null,
                    'verified_at' => null,
                    'approved_by_id' => null,
                    'approved_at' => null,
                    'rejected_by_id' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);

                activity('document_versions')
                    ->causedBy($actor)
                    ->performedOn($lockedDocument)
                    ->event('version_uploaded')
                    ->withProperties([
                        'company_id' => $lockedDocument->company_id,
                        'document_version_id' => $version->getKey(),
                        'version' => $version->version,
                        'mime_type' => $version->mime_type,
                        'size' => $version->size,
                        'checksum' => $version->checksum,
                    ])
                    ->log("uploaded document version {$version->version}");

                return $version;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($uploadedFilePath);

            throw $exception;
        }
    }

    private function sha256Checksum(string $path): string
    {
        $stream = Storage::disk('local')->readStream($path);

        if ($stream === false) {
            throw new RuntimeException('The uploaded document could not be read.');
        }

        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);

        return hash_final($hash);
    }
}
