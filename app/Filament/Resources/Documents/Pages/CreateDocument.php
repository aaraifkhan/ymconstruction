<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Actions\Documents\CreateDocumentAction;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use LogicException;

class CreateDocument extends CreateRecord
{
    use RestrictsFileUploadsToSchemaComponents;

    protected static string $resource = DocumentResource::class;

    private CreateDocumentAction $createDocument;

    public function boot(CreateDocumentAction $createDocument): void
    {
        $this->createDocument = $createDocument;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $company = Filament::getTenant();
        $user = auth()->user();

        if (! $company instanceof Company || ! $user instanceof User) {
            throw new LogicException('A company tenant and authenticated user are required.');
        }

        $uploadedFilePath = Arr::pull($data, 'uploaded_file_path');
        $originalFileName = Arr::pull($data, 'original_file_name');

        if (! is_string($uploadedFilePath) || ! is_string($originalFileName)) {
            throw new LogicException('The uploaded document file is missing.');
        }

        return $this->createDocument->handle(
            company: $company,
            attributes: $data,
            uploadedFilePath: $uploadedFilePath,
            originalFileName: $originalFileName,
            actor: $user,
        );
    }
}
