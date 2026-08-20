<?php

namespace Tests\Feature;

use App\Actions\Accounting\ProvisionCompanyAccountingFoundationAction;
use App\Actions\Accounting\ProvisionStandardAccountTemplatesAction;
use App\Actions\Assets\RegisterGeneralGroupAssetAction;
use App\Actions\Assets\ReturnGeneralAssetToPoolAction;
use App\Actions\Assets\TransferGeneralAssetCustodyAction;
use App\Enums\AccountingProfile;
use App\Enums\AssetCustodyStatus;
use App\Enums\EmployeeAssetCustodyStatus;
use App\Filament\Pages\GeneralAssetCustodyPage;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAssetCustody;
use App\Models\Employment;
use App\Models\FixedAsset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GeneralAssetCustodyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
    }

    public function test_super_admin_can_access_general_asset_custody_page(): void
    {
        $corporate = $this->provisionCompany('7 Orbit Corporate');
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(GeneralAssetCustodyPage::class)
            ->assertSuccessful()
            ->assertSee('Register & Deploy Group Asset')
            ->assertSee('Group Asset Custody & Deployment Matrix');
    }

    public function test_can_register_general_group_asset_and_deploy_to_bmc_employee(): void
    {
        $corporate = $this->provisionCompany('7 Orbit Corporate');
        $bmc = $this->provisionCompany('BMC Construction');

        $employee = Employee::factory()->create([
            'full_name' => 'Ali Khan',
        ]);

        $employment = Employment::factory()->create([
            'company_id' => $bmc->getKey(),
            'employee_id' => $employee->getKey(),
        ]);

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('accounts-hub'));
        Filament::bootCurrentPanel();

        Livewire::test(GeneralAssetCustodyPage::class)
            ->assertOk()
            ->set('data.name', 'Dell Latitude 5440 Core i7')
            ->set('data.serial_number', 'SN-DELL-99881')
            ->set('data.acquisition_cost', '275000')
            ->set('data.acquired_on', '2026-08-15')
            ->set('data.assigned_company_id', $bmc->getKey())
            ->set('data.custodian_employment_id', $employment->getKey())
            ->set('data.location', 'Gulberg Site Office Room 102')
            ->set('data.condition_on_assignment', 'Brand New')
            ->set('data.handover_notes', 'Handed over with 65W charger and laptop sleeve')
            ->call('submit');

        $asset = FixedAsset::withoutGlobalScopes()
            ->where('company_id', $corporate->getKey())
            ->where('serial_number', 'SN-DELL-99881')
            ->first();

        $this->assertNotNull($asset);
        $this->assertSame($corporate->getKey(), $asset->company_id);
        $this->assertSame($bmc->getKey(), $asset->assigned_company_id);
        $this->assertSame($employment->getKey(), $asset->custodian_employment_id);
        $this->assertSame(AssetCustodyStatus::AssignedToEmployee, $asset->custody_status);
        $this->assertSame('275000.0000', $asset->acquisition_cost);

        // Check EmployeeAssetCustody record
        $custody = EmployeeAssetCustody::query()
            ->where('fixed_asset_id', $asset->getKey())
            ->where('employment_id', $employment->getKey())
            ->first();

        $this->assertNotNull($custody);
        $this->assertSame(EmployeeAssetCustodyStatus::Issued, $custody->status);
        $this->assertSame('Brand New', $custody->issued_condition);
    }

    public function test_can_transfer_group_asset_from_bmc_to_medical_billing_employee(): void
    {
        $corporate = $this->provisionCompany('7 Orbit Corporate');
        $bmc = $this->provisionCompany('BMC Construction');
        $medicalBilling = $this->provisionCompany('7 Orbit Medical Billing');

        $emp1 = Employment::factory()->create(['company_id' => $bmc->getKey()]);
        $emp2 = Employment::factory()->create(['company_id' => $medicalBilling->getKey()]);

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        // Register initial asset to BMC employee
        $registerAction = app(RegisterGeneralGroupAssetAction::class);
        $asset = $registerAction->handle(
            ownerCompany: $corporate,
            name: 'MacBook Air M2 15-inch',
            serialNumber: 'SN-MAC-77221',
            acquisitionCost: '320000',
            assignedCompanyId: $bmc->getKey(),
            custodianEmploymentId: $emp1->getKey(),
            actor: $user,
        );

        $this->assertSame($bmc->getKey(), $asset->assigned_company_id);
        $this->assertSame($emp1->getKey(), $asset->custodian_employment_id);

        // Transfer to Medical Billing employee
        $transferAction = app(TransferGeneralAssetCustodyAction::class);
        $transferredAsset = $transferAction->handle(
            asset: $asset,
            targetCompanyId: $medicalBilling->getKey(),
            targetEmploymentId: $emp2->getKey(),
            location: 'Medical Billing Floor 2',
            condition: 'Good',
            handoverNotes: 'Transferred for medical billing audit work',
            actor: $user,
        );

        $this->assertSame($medicalBilling->getKey(), $transferredAsset->assigned_company_id);
        $this->assertSame($emp2->getKey(), $transferredAsset->custodian_employment_id);
        $this->assertSame(AssetCustodyStatus::AssignedToEmployee, $transferredAsset->custody_status);

        // Previous custody should be closed
        $oldCustody = EmployeeAssetCustody::query()
            ->where('fixed_asset_id', $asset->getKey())
            ->where('employment_id', $emp1->getKey())
            ->first();
        $this->assertSame(EmployeeAssetCustodyStatus::Transferred, $oldCustody->status);

        // New custody created
        $newCustody = EmployeeAssetCustody::query()
            ->where('fixed_asset_id', $asset->getKey())
            ->where('employment_id', $emp2->getKey())
            ->first();
        $this->assertSame(EmployeeAssetCustodyStatus::Issued, $newCustody->status);
    }

    public function test_can_return_asset_to_central_group_pool(): void
    {
        $corporate = $this->provisionCompany('7 Orbit Corporate');
        $bmc = $this->provisionCompany('BMC Construction');
        $emp = Employment::factory()->create(['company_id' => $bmc->getKey()]);

        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $asset = app(RegisterGeneralGroupAssetAction::class)->handle(
            ownerCompany: $corporate,
            name: 'Heavy Duty Site Drill Machine',
            acquisitionCost: '45000',
            assignedCompanyId: $bmc->getKey(),
            custodianEmploymentId: $emp->getKey(),
            actor: $user,
        );

        $this->assertSame(AssetCustodyStatus::AssignedToEmployee, $asset->custody_status);

        // Return to pool
        $returnedAsset = app(ReturnGeneralAssetToPoolAction::class)->handle(
            asset: $asset,
            returnCondition: 'Good',
            returnNotes: 'Site construction work completed, returned to HQ',
            actor: $user,
        );

        $this->assertNull($returnedAsset->assigned_company_id);
        $this->assertNull($returnedAsset->custodian_employment_id);
        $this->assertSame(AssetCustodyStatus::InPool, $returnedAsset->custody_status);
        $this->assertStringContainsString('Central Pool', $returnedAsset->location);

        // Custody marked returned
        $custody = EmployeeAssetCustody::query()
            ->where('fixed_asset_id', $asset->getKey())
            ->where('employment_id', $emp->getKey())
            ->first();
        $this->assertSame(EmployeeAssetCustodyStatus::Returned, $custody->status);
    }

    private function provisionCompany(string $name): Company
    {
        $company = Company::factory()->create(['name' => $name]);
        app(ProvisionStandardAccountTemplatesAction::class)->handle();
        app(ProvisionCompanyAccountingFoundationAction::class)->handle($company, AccountingProfile::Generic, CarbonImmutable::parse('2026-07-15'));

        return $company;
    }
}
