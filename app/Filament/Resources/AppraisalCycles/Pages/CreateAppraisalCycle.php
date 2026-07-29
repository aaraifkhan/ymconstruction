<?php

namespace App\Filament\Resources\AppraisalCycles\Pages;

use App\Filament\Resources\AppraisalCycles\AppraisalCycleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAppraisalCycle extends CreateRecord
{
    protected static string $resource = AppraisalCycleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Filament::getTenant()->getKey(), 'status' => 'draft'];
    }
}
