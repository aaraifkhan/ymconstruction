<?php

namespace App\Actions\Procurement;

use App\Enums\ProcurementDocumentType;
use App\Models\Company;
use App\Models\ProcurementSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveProcurementNumberAction
{
    public function handle(Company $company, ProcurementDocumentType $documentType, int $calendarYear): string
    {
        return DB::transaction(function () use ($calendarYear, $company, $documentType): string {
            $sequence = ProcurementSequence::query()
                ->whereBelongsTo($company)
                ->where('document_type', $documentType)
                ->where('calendar_year', $calendarYear)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $sequence = ProcurementSequence::query()->create([
                    'company_id' => $company->getKey(),
                    'document_type' => $documentType,
                    'calendar_year' => $calendarYear,
                    'prefix' => $documentType->prefix(),
                    'next_number' => 1,
                    'padding' => 6,
                    'is_active' => true,
                ]);
            }

            if (! $sequence->is_active) {
                throw ValidationException::withMessages(['number' => 'The procurement number sequence is inactive.']);
            }

            $number = sprintf(
                '%s-%d-%s',
                $sequence->prefix,
                $calendarYear,
                str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT),
            );

            $sequence->increment('next_number');

            return $number;
        });
    }
}
