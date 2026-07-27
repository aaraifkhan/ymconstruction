<?php

namespace App\Filament\Resources\IntercompanyTransactions\Pages;

use App\Filament\Resources\IntercompanyTransactions\IntercompanyTransactionResource;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use LogicException;

class CreateIntercompanyTransaction extends CreateRecord
{
    protected static string $resource = IntercompanyTransactionResource::class;

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
            'idempotency_key' => Str::uuid(),
        ];
    }
}
