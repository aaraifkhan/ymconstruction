<?php

namespace Tests\Feature;

use App\Actions\Accounting\AllocateSharedOperatingExpenseAction;
use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Enums\AccountingProfile;
use App\Enums\ExpenseCategory;
use App\Enums\ExpensePaymentMethod;
use App\Filament\Pages\SharedCostAllocationPage;
use App\Models\Company;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SharedCostAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_shared_operating_cost_allocation_creates_balanced_paired_journals(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $bmc = Company::factory()->create(['name' => 'BM Construction Pvt Ltd', 'slug' => 'bmc-construction']);
        $ymc = Company::factory()->create(['name' => 'YM Construction Pvt Ltd', 'slug' => 'ymc-construction']);

        app(ProvisionCompanyAccountingFoundationAction::class)->handle($bmc, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($ymc, AccountingProfile::Construction, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $maker = User::factory()->create();
        $maker->assignRole($role);

        $action = app(AllocateSharedOperatingExpenseAction::class);

        // BMC pays Office Rent PKR 100,000 via Cash (Split: BMC 60,000; YMC 40,000)
        $result = $action->handle(
            payingCompany: $bmc,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-05'),
            category: ExpenseCategory::OfficeRent,
            paymentMethod: ExpensePaymentMethod::Cash,
            totalAmount: '100000.0000',
            description: 'Shared Head Office Rent Aug 2026',
            shares: [
                ['company_id' => $bmc->getKey(), 'amount' => '60000.0000'],
                ['company_id' => $ymc->getKey(), 'amount' => '40000.0000'],
            ]
        );

        $payingJournal = $result['paying_journal'];
        $this->assertNotNull($payingJournal);
        $this->assertSame('100000.0000', $payingJournal->debit_total);
        $this->assertSame('100000.0000', $payingJournal->credit_total);
        $this->assertCount(3, $payingJournal->lines);

        // Line 1: Own rent
        $ownLine = $payingJournal->lines->firstWhere('account_code_snapshot', '5300');
        $this->assertNotNull($ownLine);
        $this->assertSame('60000.0000', $ownLine->debit);

        // Line 2: Intercompany claim from YMC (1197)
        $claimLine = $payingJournal->lines->firstWhere('account_code_snapshot', '1197');
        $this->assertNotNull($claimLine);
        $this->assertSame('40000.0000', $claimLine->debit);
        $this->assertSame($ymc->getKey(), $claimLine->related_company_id);

        // Line 3: Credit Cash (1111)
        $cashLine = $payingJournal->lines->firstWhere('account_code_snapshot', '1111');
        $this->assertNotNull($cashLine);
        $this->assertSame('100000.0000', $cashLine->credit);

        // Recipient side (YMC)
        $this->assertArrayHasKey($ymc->getKey(), $result['recipient_journals']);
        $ymcJournal = $result['recipient_journals'][$ymc->getKey()];
        $this->assertSame('40000.0000', $ymcJournal->debit_total);
        $this->assertSame('40000.0000', $ymcJournal->credit_total);

        // YMC Line 1: Debit Rent
        $ymcRentLine = $ymcJournal->lines->firstWhere('account_code_snapshot', '5300');
        $this->assertNotNull($ymcRentLine);
        $this->assertSame('40000.0000', $ymcRentLine->debit);

        // YMC Line 2: Credit Due to BMC (2195)
        $ymcPayableLine = $ymcJournal->lines->firstWhere('account_code_snapshot', '2195');
        $this->assertNotNull($ymcPayableLine);
        $this->assertSame('40000.0000', $ymcPayableLine->credit);
        $this->assertSame($bmc->getKey(), $ymcPayableLine->related_company_id);
    }

    public function test_shared_allocation_rejects_mismatched_totals(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $bmc = Company::factory()->create();
        $ymc = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($bmc, AccountingProfile::Generic, CarbonImmutable::parse('2026-08-01'));
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($ymc, AccountingProfile::Generic, CarbonImmutable::parse('2026-08-01'));

        $maker = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(AllocateSharedOperatingExpenseAction::class)->handle(
            payingCompany: $bmc,
            actor: $maker,
            date: CarbonImmutable::parse('2026-08-05'),
            category: ExpenseCategory::Utilities,
            paymentMethod: ExpensePaymentMethod::Cash,
            totalAmount: '50000.0000',
            description: 'Mismatched split test',
            shares: [
                ['company_id' => $bmc->getKey(), 'amount' => '20000.0000'],
                ['company_id' => $ymc->getKey(), 'amount' => '20000.0000'], // Sum = 40,000 != 50,000
            ]
        );
    }

    public function test_shared_cost_allocation_page_mounts(): void
    {
        app(ProvisionStandardAccountTemplatesAction::class)->handle();

        $company = Company::factory()->create();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-08-01'));

        $role = Role::findOrCreate('super_admin');
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        Livewire::test(SharedCostAllocationPage::class)
            ->assertSuccessful();
    }
}
