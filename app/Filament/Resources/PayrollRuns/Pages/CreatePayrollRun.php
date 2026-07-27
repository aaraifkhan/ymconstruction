<?php

namespace App\Filament\Resources\PayrollRuns\Pages;

use App\Filament\Resources\PayrollRuns\PayrollRunResource;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use LogicException;

class CreatePayrollRun extends CreateRecord
{
    protected static string $resource = PayrollRunResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = Filament::getTenant();
        $actor = auth()->user();
        if (! $company instanceof Company || ! $actor instanceof User) {
            throw new LogicException('A company and authenticated user are required.');
        }

        return [...$data, 'company_id' => $company->getKey(), 'created_by_id' => $actor->getKey()];
    }
}
