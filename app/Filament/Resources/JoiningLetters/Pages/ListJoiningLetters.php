<?php

namespace App\Filament\Resources\JoiningLetters\Pages;

use App\Filament\Resources\JoiningLetters\JoiningLetterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJoiningLetters extends ListRecords
{
    protected static string $resource = JoiningLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
