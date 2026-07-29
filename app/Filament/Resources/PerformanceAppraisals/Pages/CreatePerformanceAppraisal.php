<?php

namespace App\Filament\Resources\PerformanceAppraisals\Pages;

use App\Filament\Resources\PerformanceAppraisals\PerformanceAppraisalResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePerformanceAppraisal extends CreateRecord
{
    protected static string $resource = PerformanceAppraisalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'created_by_id' => auth()->id(),
            'status' => 'draft',
        ];
    }
}
