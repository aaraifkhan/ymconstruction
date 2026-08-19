<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\JournalLine;

class CheckAccountAvailableBalanceAction
{
    /**
     * Get the posted ledger balance for an account.
     * For Debit-normal accounts (Assets/Expenses): Balance = Debit - Credit
     * For Credit-normal accounts (Liabilities/Equity/Revenue): Balance = Credit - Debit
     */
    public function getAccountBalance(Company|int $company, Account|int $account): string
    {
        $companyId = $company instanceof Company ? $company->getKey() : $company;
        $accountModel = $account instanceof Account ? $account : Account::query()->findOrFail($account);

        $postedLines = JournalLine::query()
            ->where('company_id', $companyId)
            ->where('account_id', $accountModel->getKey())
            ->whereHas('journalEntry', fn ($query) => $query->whereIn('status', [
                JournalStatus::Posted->value,
                JournalStatus::Reversed->value,
            ]));

        $debitSum = (string) (clone $postedLines)->sum('debit');
        $creditSum = (string) (clone $postedLines)->sum('credit');

        $isDebitNormal = ($accountModel->normal_balance ?? NormalBalance::Debit) === NormalBalance::Debit;

        return $isDebitNormal
            ? bcsub($debitSum, $creditSum, 4)
            : bcsub($creditSum, $debitSum, 4);
    }

    /**
     * Get the posted ledger balance for a specific company bank account.
     */
    public function getBankAccountBalance(Company|int $company, CompanyBankAccount|int $bankAccount): string
    {
        $companyId = $company instanceof Company ? $company->getKey() : $company;
        $bankAccountId = $bankAccount instanceof CompanyBankAccount ? $bankAccount->getKey() : $bankAccount;

        $postedLines = JournalLine::query()
            ->where('company_id', $companyId)
            ->where('company_bank_account_id', $bankAccountId)
            ->whereHas('journalEntry', fn ($query) => $query->whereIn('status', [
                JournalStatus::Posted->value,
                JournalStatus::Reversed->value,
            ]));

        $debitSum = (string) (clone $postedLines)->sum('debit');
        $creditSum = (string) (clone $postedLines)->sum('credit');

        return bcsub($debitSum, $creditSum, 4);
    }

    /**
     * Check if an account is a physical/petty cash or liquid bank account.
     */
    public function isCashOrBankAccount(Account $account): bool
    {
        if (in_array($account->system_key, [
            AccountingMappingKey::DefaultCash->value,
            AccountingMappingKey::SitePettyCash->value,
            AccountingMappingKey::BankAccounts->value,
        ], true)) {
            return true;
        }

        $code = (int) $account->code;

        return $code >= 1111 && $code <= 1129;
    }

    /**
     * Check if an account is strictly a physical cash or petty cash account (where negative balance is physically impossible).
     */
    public function isStrictCashAccount(Account $account): bool
    {
        if (in_array($account->system_key, [
            AccountingMappingKey::DefaultCash->value,
            AccountingMappingKey::SitePettyCash->value,
        ], true)) {
            return true;
        }

        $code = (int) $account->code;

        return $code >= 1111 && $code <= 1119;
    }
}
