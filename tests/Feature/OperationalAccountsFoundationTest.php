<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingMappingKey;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Models\AccountingMapping;
use App\Models\Company;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalAccountsFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_operational_accounts_and_system_keys_are_provisioned(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $bmc = Company::factory()->create(['name' => 'BM Construction Pvt Ltd', 'slug' => 'bmc-construction']);
        $ymc = Company::factory()->create(['name' => 'YM Construction Pvt Ltd', 'slug' => 'ymc-construction']);

        $action = app(ProvisionCompanyAccountingFoundationAction::class);
        $action->handle($bmc, AccountingProfile::Construction);
        $action->handle($ymc, AccountingProfile::Construction);

        // 1. Verify Petty Cash, Director Loan, Director Advance mappings exist
        $this->assertNotNull(AccountingMapping::where('company_id', $bmc->getKey())->where('system_key', AccountingMappingKey::SitePettyCash)->first());
        $this->assertNotNull(AccountingMapping::where('company_id', $bmc->getKey())->where('system_key', AccountingMappingKey::DirectorCashAdvance)->first());
        $this->assertNotNull(AccountingMapping::where('company_id', $bmc->getKey())->where('system_key', AccountingMappingKey::DirectorLoan)->first());
        $this->assertNotNull(AccountingMapping::where('company_id', $bmc->getKey())->where('system_key', AccountingMappingKey::StaffReimbursementPayable)->first());
        $this->assertNotNull(AccountingMapping::where('company_id', $bmc->getKey())->where('system_key', AccountingMappingKey::RentalPayable)->first());

        // 2. Verify Bidding Accounts exist for Construction companies
        $this->assertNotNull($ymc->accounts()->where('code', '5051')->first()); // Tender Fee
        $this->assertNotNull($ymc->accounts()->where('code', '5052')->first()); // Tender Documentation
        $this->assertNotNull($ymc->accounts()->where('code', '5058')->first()); // Misc Bidding

        // 3. Verify Operational Operating Expense accounts exist
        $this->assertNotNull($bmc->accounts()->where('code', '5150')->first()); // Staff Engagement
        $this->assertNotNull($bmc->accounts()->where('code', '5450')->first()); // Cleaning
        $this->assertNotNull($bmc->accounts()->where('code', '5550')->first()); // Mobile & Telephone
        $this->assertNotNull($bmc->accounts()->where('code', '6850')->first()); // Hotel & Accommodation

        // 4. Verify Direct Project Cost accounts exist
        $this->assertNotNull($ymc->accounts()->where('code', '7290')->first()); // Site Misc
        $this->assertNotNull($ymc->accounts()->where('code', '7295')->first()); // Site Legal & Surveyor

        // 5. Verify ExpensePaymentMethod enum labels
        $this->assertSame('Head Office Cash', ExpensePaymentMethod::Cash->getLabel());
        $this->assertSame('Director Funded (Due to Director)', ExpensePaymentMethod::Director->getLabel());
        $this->assertSame('Petty Cash Float', ExpensePaymentMethod::PettyCash->getLabel());

        // 6. Verify ExpenseCategory accounts link to valid codes
        foreach (ExpenseCategory::cases() as $category) {
            $code = $category->defaultAccountCode();
            $this->assertNotEmpty($code);
            $account = $ymc->accounts()->where('code', $code)->first();
            $this->assertNotNull($account, "Account with code {$code} for category {$category->value} should exist in YMC accounts.");
        }
    }
}
