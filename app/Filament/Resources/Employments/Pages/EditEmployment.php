<?php

namespace App\Filament\Resources\Employments\Pages;

use App\Filament\Resources\Employments\EmploymentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class EditEmployment extends EditRecord
{
    protected static string $resource = EmploymentResource::class;

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
        if (! Gate::allows('manageHrVerification', $this->record)) {
            $data = Arr::except($data, [
                'interviewed_by_id',
                'documents_verified_by_id',
                'appointment_letter_issued',
            ]);
        }

        if (! Gate::allows('viewHrNotes', $this->record)) {
            $data = Arr::except($data, ['hr_notes']);
        }

        return $data;
    }
}
