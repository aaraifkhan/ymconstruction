<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Enums\JournalStatus;
use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\FinancialPeriod;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lines = $this->form->getRawState()['lines'] ?? $data['lines'] ?? [];
        if (! empty($lines)) {
            $this->validateDoubleEntry($lines);
        }

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

    /** @param array<array-key, array<string, mixed>> $lines */
    private function validateDoubleEntry(array $lines): void
    {
        if (count($lines) < 2) {
            return;
        }

        $debitTotal = '0.0000';
        $creditTotal = '0.0000';
        $hasDebit = false;
        $hasCredit = false;

        foreach ($lines as $index => $line) {
            $debit = (string) ($line['debit'] ?? '0');
            $credit = (string) ($line['credit'] ?? '0');

            if (bccomp($debit, '0.0000', 4) > 0 && bccomp($credit, '0.0000', 4) > 0) {
                throw ValidationException::withMessages(["lines.{$index}" => 'A single line cannot have both Debit and Credit amounts.']);
            }

            if (bccomp($debit, '0.0000', 4) > 0) {
                $hasDebit = true;
                $debitTotal = bcadd($debitTotal, $debit, 4);
            }

            if (bccomp($credit, '0.0000', 4) > 0) {
                $hasCredit = true;
                $creditTotal = bcadd($creditTotal, $credit, 4);
            }
        }

        if (! $hasDebit || ! $hasCredit) {
            throw ValidationException::withMessages(['lines' => 'A voucher must have at least one Debit line and at least one Credit line.']);
        }

        if (bccomp($debitTotal, $creditTotal, 4) !== 0) {
            $diff = bcsub($debitTotal, $creditTotal, 4);
            $diffFormatted = number_format(abs((float) $diff), 2);
            $debitFormatted = number_format((float) $debitTotal, 2);
            $creditFormatted = number_format((float) $creditTotal, 2);
            throw ValidationException::withMessages([
                'lines' => "Voucher is out of balance. Total Debit (PKR {$debitFormatted}) must equal Total Credit (PKR {$creditFormatted}). Difference: PKR {$diffFormatted}.",
            ]);
        }
    }
}
