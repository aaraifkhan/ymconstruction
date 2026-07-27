<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Enums\JournalStatus;
use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\FinancialPeriod;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $period = FinancialPeriod::query()->whereKey($data['financial_period_id'])
            ->where('company_id', Filament::getTenant()->getKey())->firstOrFail();

        return [
            ...$data,
            'company_id' => Filament::getTenant()->getKey(),
            'financial_year_id' => $period->financial_year_id,
            'idempotency_key' => Str::uuid(),
            'status' => JournalStatus::Draft,
            'prepared_by_id' => Filament::auth()->id(),
        ];
    }
}
