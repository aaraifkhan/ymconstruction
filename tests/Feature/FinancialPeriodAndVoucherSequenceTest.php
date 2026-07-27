<?php

namespace Tests\Feature;

use App\Actions\Accounting\CloseFinancialPeriodAction;
use App\Actions\Accounting\LockFinancialPeriodAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Accounting\ReopenFinancialPeriodAction;
use App\Actions\Accounting\ReserveVoucherNumberAction;
use App\Enums\AccountingProfile;
use App\Enums\FinancialPeriodStatus;
use App\Enums\VoucherType;
use App\Models\Company;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialPeriodAndVoucherSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_close_lock_and_reopen_preserve_evidence(): void
    {
        [$company, $user] = $this->foundation();
        $period = $company->financialPeriods()->firstOrFail();

        app(CloseFinancialPeriodAction::class)->handle($period, $user);
        app(LockFinancialPeriodAction::class)->handle($period, $user);
        app(ReopenFinancialPeriodAction::class)->handle($period, $user, 'Approved correction');

        $period->refresh();
        $this->assertSame(FinancialPeriodStatus::Open, $period->status);
        $this->assertNotNull($period->closed_at);
        $this->assertNotNull($period->locked_at);
        $this->assertSame('Approved correction', $period->reopen_reason);
    }

    public function test_voucher_numbers_are_sequential_per_company_type_and_year(): void
    {
        [$company] = $this->foundation();
        $sequence = $company->voucherSequences()->where('voucher_type', VoucherType::Journal)->firstOrFail();
        $reserve = app(ReserveVoucherNumberAction::class);

        $this->assertSame('JV-2026-000001', $reserve->handle($sequence));
        $this->assertSame('JV-2026-000002', $reserve->handle($sequence));
    }

    /** @return array{Company, User} */
    private function foundation(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-25'));

        return [$company, $user];
    }
}
