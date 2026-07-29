<?php

namespace App\Filament\Resources\Employments\Pages;

use App\Filament\Resources\Employments\EmploymentResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateEmployment extends CreateRecord
{
    protected static string $resource = EmploymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->getKey();

        if (! (auth()->user()?->can('ManageHrVerification:Employment') ?? false)) {
            $data = Arr::except($data, [
                'interviewed_by_id',
                'documents_verified_by_id',
                'appointment_letter_issued',
            ]);
        }

        if (! (auth()->user()?->can('ViewHrNotes:Employment') ?? false)) {
            $data = Arr::except($data, ['hr_notes']);
        }

        return $data;
    }
}
