<?php

namespace App\Filament\Resources\AccountTemplates\Pages;

use App\Filament\Resources\AccountTemplates\AccountTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccountTemplates extends ListRecords
{
    protected static string $resource = AccountTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
