<?php

namespace App\Actions\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Enums\OpeningBalanceStatus;
use App\Models\OpeningBalanceBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ValidateOpeningBalanceBatchAction
{
    public function handle(OpeningBalanceBatch $batch, User $actor): OpeningBalanceBatch
    {
        Gate::forUser($actor)->authorize('validate', $batch);

        return DB::transaction(function () use ($batch, $actor): OpeningBalanceBatch {
            $batch = OpeningBalanceBatch::query()->with(['lines.account', 'financialPeriod'])
                ->whereKey($batch)->lockForUpdate()->firstOrFail();
            if ($batch->status !== OpeningBalanceStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only a draft opening-balance batch may be validated.']);
            }
            if ((int) $batch->prepared_by_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages(['validated_by_id' => 'The preparer cannot validate the same opening-balance batch.']);
            }
            if ($batch->financialPeriod->status !== FinancialPeriodStatus::Open) {
                throw ValidationException::withMessages(['financial_period_id' => 'Opening balances require an open period.']);
            }
            if ($batch->lines->count() < 2) {
                throw ValidationException::withMessages(['lines' => 'An opening-balance batch requires at least two lines.']);
            }

            $debitTotal = $batch->lines->reduce(fn (string $total, $line): string => bcadd($total, (string) $line->debit, 4), '0.0000');
            $creditTotal = $batch->lines->reduce(fn (string $total, $line): string => bcadd($total, (string) $line->credit, 4), '0.0000');
            if (bccomp($debitTotal, '0.0000', 4) !== 1 || bccomp($debitTotal, $creditTotal, 4) !== 0) {
                throw ValidationException::withMessages(['lines' => 'Opening-balance debits and credits must be equal and greater than zero.']);
            }
            foreach ($batch->lines as $line) {
                if (! $line->account->is_active || $line->account->children()->exists()) {
                    throw ValidationException::withMessages(['lines' => "Account {$line->account->code} is inactive or non-posting."]);
                }
            }

            $batch->update([
                'status' => OpeningBalanceStatus::Validated, 'debit_total' => $debitTotal,
                'credit_total' => $creditTotal, 'validated_by_id' => $actor->getKey(), 'validated_at' => now(),
            ]);
            activity('opening_balances')->causedBy($actor)->performedOn($batch)->event('validated')
                ->withProperties(['company_id' => $batch->company_id, 'line_count' => $batch->lines->count(), 'debit_total' => $debitTotal, 'credit_total' => $creditTotal])
                ->log('validated opening-balance batch');

            return $batch->refresh();
        });
    }
}
