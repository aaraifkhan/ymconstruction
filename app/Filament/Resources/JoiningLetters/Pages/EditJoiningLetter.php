<?php

namespace App\Filament\Resources\JoiningLetters\Pages;

use App\Filament\Resources\JoiningLetters\Actions\JoiningLetterWorkflowActions;
use App\Filament\Resources\JoiningLetters\JoiningLetterResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class EditJoiningLetter extends EditRecord
{
    protected static string $resource = JoiningLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            JoiningLetterWorkflowActions::regenerate(),
            JoiningLetterWorkflowActions::submit(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (Gate::allows('manageCompensation', $this->record)) {
            return $data;
        }

        return Arr::except($data, ['compensation_amount', 'currency_code']);
    }
}
