<?php

namespace App\Filament\Resources\AssetDisposals\Pages;

use App\Filament\Resources\AssetDisposals\AssetDisposalResource;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use LogicException;

class CreateAssetDisposal extends CreateRecord
{
    protected static string $resource = AssetDisposalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = Filament::getTenant();
        $actor = Filament::auth()->user();
        if (! $company instanceof Company || ! $actor instanceof User) {
            throw new LogicException('A company and authenticated user are required.');
        }

        return [
            ...$data,
            'company_id' => $company->getKey(),
            'prepared_by_id' => $actor->getKey(),
            'cost_amount' => 0,
            'accumulated_depreciation_amount' => 0,
            'carrying_amount' => 0,
        ];
    }
}
