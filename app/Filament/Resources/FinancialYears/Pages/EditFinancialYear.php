<?php

namespace App\Filament\Resources\FinancialYears\Pages;

use App\Filament\Resources\FinancialYears\FinancialYearResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFinancialYear extends EditRecord
{
    protected static string $resource = FinancialYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
