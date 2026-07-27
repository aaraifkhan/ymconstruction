<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (Gate::allows('manageSensitive', $this->record)) {
            return $data;
        }

        return Arr::except($data, [
            'father_or_husband_name',
            'cnic',
            'date_of_birth',
            'gender',
            'marital_status',
            'nationality',
            'blood_group',
            'address',
            'mobile',
            'alternate_contact',
            'email',
        ]);
    }
}
