<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountingFoundationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_parent_must_belong_to_same_company(): void
    {
        $first = Company::factory()->create();
        $second = Company::factory()->create();
        $parent = $this->account($first, '1000');

        $this->expectException(ValidationException::class);
        $this->account($second, '1010', $parent->getKey());
    }

    public function test_control_account_cannot_allow_manual_posting(): void
    {
        $this->expectException(ValidationException::class);
        Account::create([
            'company_id' => Company::factory()->create()->getKey(), 'code' => '1130', 'name' => 'Receivables',
            'account_type' => AccountType::Asset, 'reporting_group' => 'current_assets',
            'normal_balance' => NormalBalance::Debit, 'is_control_account' => true, 'allows_manual_posting' => true,
        ]);
    }

    private function account(Company $company, string $code, ?int $parentId = null): Account
    {
        return Account::create([
            'company_id' => $company->getKey(), 'parent_id' => $parentId, 'code' => $code, 'name' => $code,
            'account_type' => AccountType::Asset, 'reporting_group' => 'assets',
            'normal_balance' => NormalBalance::Debit, 'allows_manual_posting' => false,
        ]);
    }
}
