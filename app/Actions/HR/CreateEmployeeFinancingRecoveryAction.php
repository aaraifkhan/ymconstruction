<?php

namespace App\Actions\HR;

use App\Enums\EmployeeFinancingStatus;
use App\Enums\TreasuryCounterpartyType;
use App\Enums\TreasuryPurpose;
use App\Enums\TreasuryStatus;
use App\Enums\TreasuryTransactionType;
use App\Models\Account;
use App\Models\CompanyBankAccount;
use App\Models\EmployeeFinancing;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateEmployeeFinancingRecoveryAction
{
    public function handle(
        EmployeeFinancing $financing,
        string $amount,
        Account $destinationAccount,
        ?CompanyBankAccount $bankAccount,
        CarbonImmutable $transactionDate,
        User $actor,
    ): TreasuryTransaction {
        Gate::forUser($actor)->authorize('recover', $financing);

        return DB::transaction(function () use ($financing, $amount, $destinationAccount, $bankAccount, $transactionDate, $actor): TreasuryTransaction {
            $financing = EmployeeFinancing::query()->whereKey($financing)->lockForUpdate()->firstOrFail();
            if ($financing->status !== EmployeeFinancingStatus::Active
                || bccomp($amount, '0', 4) !== 1
                || bccomp($amount, $financing->outstandingAmount(), 4) === 1
                || (int) $destinationAccount->company_id !== (int) $financing->company_id
                || ($bankAccount !== null && (int) $bankAccount->company_id !== (int) $financing->company_id)) {
                throw ValidationException::withMessages(['amount' => 'Recovery requires a positive amount within the active same-company financing balance.']);
            }

            return TreasuryTransaction::query()->create([
                'company_id' => $financing->company_id,
                'employment_id' => $financing->employment_id,
                'employee_financing_id' => $financing->getKey(),
                'destination_account_id' => $destinationAccount->getKey(),
                'destination_company_bank_account_id' => $bankAccount?->getKey(),
                'type' => TreasuryTransactionType::Receipt,
                'purpose' => TreasuryPurpose::Advance,
                'counterparty_type' => TreasuryCounterpartyType::Employment,
                'transaction_date' => $transactionDate,
                'status' => TreasuryStatus::Draft,
                'currency_code' => 'PKR',
                'amount' => $amount,
                'description' => "{$financing->type->label()} recovery {$financing->reference_number}",
                'external_reference' => $financing->reference_number,
                'prepared_by_id' => $actor->getKey(),
            ]);
        }, 3);
    }
}
