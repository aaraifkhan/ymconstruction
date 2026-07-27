<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Filament\Resources\Accounts\AccountResource;
use App\Models\Account;
use App\Models\AccountingMapping;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountingFoundationProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_company_foundation_is_idempotent_and_keeps_company_snapshots(): void
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        $action = app(ProvisionCompanyAccountingFoundationAction::class);

        $action->handle($company, AccountingProfile::Construction);
        $count = $company->accounts()->count();
        $account = $company->accounts()->where('code', '4100')->firstOrFail();
        $account->update(['name' => 'Company-specific construction income']);
        $action->handle($company, AccountingProfile::Construction);

        $this->assertSame($count, $company->accounts()->count());
        $this->assertSame('Company-specific construction income', $account->fresh()->name);
        $this->assertTrue($company->accounts()->where('code', '4100')->firstOrFail()->is_active);
        $this->assertFalse($company->accounts()->where('code', '4200')->firstOrFail()->is_active);
        $this->assertCount(12, $company->financialPeriods);
        $this->assertCount(14, $company->voucherSequences);
        $this->assertSame(1, $company->apMatchingSettings()->count());
        $this->assertSame('0.0000', $company->apMatchingSettings()->firstOrFail()->rate_tolerance_percentage);
    }

    public function test_each_bank_account_gets_a_company_gl_account_and_mapping(): void
    {
        $company = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic);

        $bank = CompanyBankAccount::factory()->for($company)->create();

        $mapping = AccountingMapping::where('company_bank_account_id', $bank->getKey())->firstOrFail();
        $this->assertSame($company->getKey(), $mapping->account->company_id);
        $this->assertSame('1120-B'.str_pad((string) $bank->getKey(), 6, '0', STR_PAD_LEFT), $mapping->account->code);
        $this->assertTrue($mapping->account->allows_manual_posting);
        $this->assertSame(1, Account::where('company_id', $company->getKey())->where('code', $mapping->account->code)->count());
    }

    public function test_account_resource_and_policy_are_company_scoped(): void
    {
        $currentCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        $provision = app(ProvisionCompanyAccountingFoundationAction::class);
        $provision->handle($currentCompany, AccountingProfile::Generic);
        $provision->handle($otherCompany, AccountingProfile::Generic);
        $user = User::factory()->create();
        $user->companies()->attach($currentCompany, ['is_active' => true, 'can_access_descendants' => false]);
        $user->givePermissionTo([Permission::findOrCreate('ViewAny:Account'), Permission::findOrCreate('View:Account')]);
        $currentAccount = $currentCompany->accounts()->firstOrFail();
        $otherAccount = $otherCompany->accounts()->firstOrFail();

        $this->actingAs($user);
        Filament::setTenant($currentCompany);
        Filament::bootCurrentPanel();

        $accountIds = AccountResource::getEloquentQuery()->pluck('company_id')->unique()->all();

        $this->assertSame([$currentCompany->getKey()], $accountIds);
        $this->assertTrue(Gate::allows('view', $currentAccount));
        $this->assertFalse(Gate::allows('view', $otherAccount));
    }
}
