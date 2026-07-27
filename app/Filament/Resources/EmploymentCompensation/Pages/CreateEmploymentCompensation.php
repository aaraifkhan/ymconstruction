<?php

namespace App\Filament\Resources\EmploymentCompensation\Pages;

use App\Filament\Resources\EmploymentCompensation\EmploymentCompensationResource;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use LogicException;

class CreateEmploymentCompensation extends CreateRecord
{
    protected static string $resource = EmploymentCompensationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = Filament::getTenant();
        $actor = auth()->user();

        if (! $company instanceof Company || ! $actor instanceof User) {
            throw new LogicException('A company and authenticated user are required.');
        }

        return [
            ...$data,
            'company_id' => $company->getKey(),
            'created_by_id' => $actor->getKey(),
        ];
    }
}
