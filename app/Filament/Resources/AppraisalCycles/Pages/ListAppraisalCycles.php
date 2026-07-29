<?php

namespace App\Filament\Resources\AppraisalCycles\Pages;

use App\Filament\Resources\AppraisalCycles\AppraisalCycleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppraisalCycles extends ListRecords
{
    protected static string $resource = AppraisalCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
