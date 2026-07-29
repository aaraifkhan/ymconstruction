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

class CreateEmployeeFinancingDisbursementAction
{
    public function handle(
        EmployeeFinancing $financing,
        Account $sourceAccount,
        ?CompanyBankAccount $bankAccount,
        CarbonImmutable $transactionDate,
        User $actor,
    ): TreasuryTransaction {
        Gate::forUser($actor)->authorize('disburse', $financing);

        return DB::transaction(function () use ($financing, $sourceAccount, $bankAccount, $transactionDate, $actor): TreasuryTransaction {
            $financing = EmployeeFinancing::query()->whereKey($financing)->lockForUpdate()->firstOrFail();
            if ($financing->status === EmployeeFinancingStatus::DisbursementPending) {
                return $financing->treasuryTransactions()->whereNot('status', TreasuryStatus::Reversed)->latest('id')->firstOrFail();
            }
            if ($financing->status !== EmployeeFinancingStatus::Approved
                || bccomp((string) $financing->finance_charge, '0', 4) !== 0
                || (int) $sourceAccount->company_id !== (int) $financing->company_id
                || ($bankAccount !== null && (int) $bankAccount->company_id !== (int) $financing->company_id)) {
                throw ValidationException::withMessages(['status' => 'Approved zero-charge financing requires a same-company mapped cash/bank source. Finance-charge recognition needs an approved accounting mapping.']);
            }
            $treasury = TreasuryTransaction::query()->create([
                'company_id' => $financing->company_id,
                'employment_id' => $financing->employment_id,
                'employee_financing_id' => $financing->getKey(),
                'source_account_id' => $sourceAccount->getKey(),
                'source_company_bank_account_id' => $bankAccount?->getKey(),
                'type' => TreasuryTransactionType::Payment,
                'purpose' => TreasuryPurpose::Advance,
                'counterparty_type' => TreasuryCounterpartyType::Employment,
                'transaction_date' => $transactionDate,
                'status' => TreasuryStatus::Draft,
                'currency_code' => 'PKR',
                'amount' => $financing->principal_amount,
                'description' => "{$financing->type->label()} disbursement {$financing->reference_number}",
                'external_reference' => $financing->reference_number,
                'prepared_by_id' => $actor->getKey(),
            ]);
            $financing->update(['status' => EmployeeFinancingStatus::DisbursementPending]);
            activity('employee_financings')->causedBy($actor)->performedOn($financing)->event('disbursement_created')
                ->withProperties(['company_id' => $financing->company_id, 'treasury_transaction_id' => $treasury->getKey()])
                ->log('created employee financing disbursement');

            return $treasury;
        }, 3);
    }
}
