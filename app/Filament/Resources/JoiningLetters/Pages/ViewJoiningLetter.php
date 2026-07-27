<?php

namespace App\Filament\Resources\JoiningLetters\Pages;

use App\Filament\Resources\JoiningLetters\Actions\JoiningLetterWorkflowActions;
use App\Filament\Resources\JoiningLetters\JoiningLetterResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJoiningLetter extends ViewRecord
{
    protected static string $resource = JoiningLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            JoiningLetterWorkflowActions::regenerate(),
            JoiningLetterWorkflowActions::submit(),
            JoiningLetterWorkflowActions::approve(),
            JoiningLetterWorkflowActions::reject(),
            JoiningLetterWorkflowActions::issue(),
            JoiningLetterWorkflowActions::recordAcceptance(),
        ];
    }
}
