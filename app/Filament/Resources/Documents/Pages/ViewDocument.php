<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\Actions\DocumentFileActions;
use App\Filament\Resources\Documents\Actions\DocumentWorkflowActions;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;

class ViewDocument extends ViewRecord
{
    use RestrictsFileUploadsToSchemaComponents;

    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DocumentFileActions::previewCurrent(),
            DocumentFileActions::downloadCurrent(),
            DocumentWorkflowActions::uploadVersion(),
            DocumentWorkflowActions::verify(),
            DocumentWorkflowActions::approve(),
            DocumentWorkflowActions::reject(),
        ];
    }
}
