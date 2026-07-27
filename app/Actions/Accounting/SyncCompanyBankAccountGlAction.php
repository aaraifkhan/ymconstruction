<?php

namespace App\Actions\Accounting;

use App\Enums\AccountingMappingKey;
use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\CompanyBankAccount;
use Illuminate\Support\Facades\Schema;

class SyncCompanyBankAccountGlAction
{
    public function handle(CompanyBankAccount $bankAccount): ?Account
    {
        if (! Schema::hasTable('accounting_settings') || ! $bankAccount->company->accountingSettings()->exists()) {
            return null;
        }

        $parent = Account::query()->where('company_id', $bankAccount->company_id)
            ->where('system_key', AccountingMappingKey::BankAccounts)->firstOrFail();
        $account = Account::withTrashed()->firstOrCreate(
            ['company_id' => $bankAccount->company_id, 'code' => '1120-B'.str_pad((string) $bankAccount->getKey(), 6, '0', STR_PAD_LEFT)],
            [
                'parent_id' => $parent->getKey(), 'name' => "{$bankAccount->bank_name} — {$bankAccount->account_title}",
                'account_type' => AccountType::Asset, 'reporting_group' => 'current_assets',
                'normal_balance' => NormalBalance::Debit, 'allows_manual_posting' => true,
                'is_system_generated' => true, 'is_active' => $bankAccount->is_active && ! $bankAccount->trashed(),
            ],
        );
        $account->restore();
        $account->update(['name' => "{$bankAccount->bank_name} — {$bankAccount->account_title}", 'is_active' => $bankAccount->is_active && ! $bankAccount->trashed()]);

        AccountingMapping::updateOrCreate(
            ['company_bank_account_id' => $bankAccount->getKey()],
            ['company_id' => $bankAccount->company_id, 'account_id' => $account->getKey(), 'system_key' => null, 'is_active' => $account->is_active],
        );

        return $account;
    }
}
