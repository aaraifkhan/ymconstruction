<?php

namespace Tests\Feature;

use App\Actions\Accounting\PostOpeningBalanceBatchAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\ValidateOpeningBalanceBatchAction;
use App\Enums\AccountingProfile;
use App\Enums\JournalStatus;
use App\Enums\OpeningBalanceStatus;
use App\Models\Company;
use App\Models\OpeningBalanceBatch;
use App\Models\OpeningBalanceLine;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpeningBalanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_validated_opening_balances_post_once_through_the_normal_ledger(): void
    {
        [$company, $maker, $validator, $poster] = $this->foundation();
        $batch = $this->batch($company, $maker);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $capital = $company->accounts()->where('code', '3100')->firstOrFail();
        OpeningBalanceLine::create(['opening_balance_batch_id' => $batch->getKey(), 'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $cash->getKey(), 'debit' => 250000]);
        OpeningBalanceLine::create(['opening_balance_batch_id' => $batch->getKey(), 'company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $capital->getKey(), 'credit' => 250000]);

        app(ValidateOpeningBalanceBatchAction::class)->handle($batch, $validator);
        $entry = app(PostOpeningBalanceBatchAction::class)->handle($batch, $poster);
        $again = app(PostOpeningBalanceBatchAction::class)->handle($batch, $poster);

        $this->assertSame(OpeningBalanceStatus::Posted, $batch->fresh()->status);
        $this->assertSame(JournalStatus::Posted, $entry->status);
        $this->assertSame('OB-2026-000001', $entry->voucher_number);
        $this->assertSame($entry->getKey(), $again->getKey());
        $this->assertSame(1, $company->journalEntries()->count());
    }

    public function test_unbalanced_opening_batch_is_rejected(): void
    {
        [$company, $maker, $validator] = $this->foundation();
        $batch = $this->batch($company, $maker);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $capital = $company->accounts()->where('code', '3100')->firstOrFail();
        OpeningBalanceLine::create(['opening_balance_batch_id' => $batch->getKey(), 'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $cash->getKey(), 'debit' => 100]);
        OpeningBalanceLine::create(['opening_balance_batch_id' => $batch->getKey(), 'company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $capital->getKey(), 'credit' => 99]);

        $this->expectException(ValidationException::class);
        app(ValidateOpeningBalanceBatchAction::class)->handle($batch, $validator);
    }

    public function test_preparer_cannot_validate_own_opening_balance_batch(): void
    {
        [$company, $maker] = $this->foundation();
        $batch = $this->batch($company, $maker);
        $cash = $company->accounts()->where('code', '1111')->firstOrFail();
        $capital = $company->accounts()->where('code', '3100')->firstOrFail();
        OpeningBalanceLine::create(['opening_balance_batch_id' => $batch->getKey(), 'company_id' => $company->getKey(), 'line_number' => 1, 'account_id' => $cash->getKey(), 'debit' => 100]);
        OpeningBalanceLine::create(['opening_balance_batch_id' => $batch->getKey(), 'company_id' => $company->getKey(), 'line_number' => 2, 'account_id' => $capital->getKey(), 'credit' => 100]);

        $this->expectException(ValidationException::class);
        app(ValidateOpeningBalanceBatchAction::class)->handle($batch, $maker);
    }

    /** @return array{Company, User, User, User} */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));
        $role = Role::findOrCreate('super_admin');
        $users = User::factory()->count(3)->create();
        $users->each->assignRole($role);

        return [$company, ...$users->all()];
    }

    private function batch(Company $company, User $maker): OpeningBalanceBatch
    {
        $period = $company->financialPeriods()->where('period_number', 1)->firstOrFail();

        return OpeningBalanceBatch::create([
            'company_id' => $company->getKey(), 'financial_year_id' => $period->financial_year_id,
            'financial_period_id' => $period->getKey(), 'opening_date' => '2026-07-01',
            'source_name' => 'Approved trial balance', 'idempotency_key' => Str::uuid(),
            'prepared_by_id' => $maker->getKey(),
        ]);
    }
}
