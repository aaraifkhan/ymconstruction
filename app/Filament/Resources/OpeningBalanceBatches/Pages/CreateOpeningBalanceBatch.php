<?php

namespace App\Filament\Resources\OpeningBalanceBatches\Pages;

use App\Filament\Resources\OpeningBalanceBatches\OpeningBalanceBatchResource;
use App\Models\FinancialPeriod;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateOpeningBalanceBatch extends CreateRecord
{
    protected static string $resource = OpeningBalanceBatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $period = FinancialPeriod::query()->whereKey($data['financial_period_id'])
            ->where('company_id', Filament::getTenant()->getKey())->firstOrFail();

        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'financial_year_id' => $period->financial_year_id,
            'idempotency_key' => Str::uuid(),
            'prepared_by_id' => Filament::auth()->id(),
        ];
    }
}
