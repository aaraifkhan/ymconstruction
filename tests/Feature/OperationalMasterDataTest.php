<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\PartyRole;
use App\Enums\ProjectStatus;
use App\Enums\TaxCalculationMethod;
use App\Enums\TaxCodeType;
use App\Filament\Resources\CostCenters\CostCenterResource;
use App\Filament\Resources\CostCenters\Pages\CreateCostCenter;
use App\Filament\Resources\ItemCategories\Pages\CreateItemCategory;
use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Parties\Pages\CreateParty;
use App\Filament\Resources\Parties\PartyResource;
use App\Filament\Resources\ProjectBudgets\Pages\CreateProjectBudget;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\ProjectSites\Pages\CreateProjectSite;
use App\Filament\Resources\TaxCodes\Pages\CreateTaxCode;
use App\Filament\Resources\UnitOfMeasures\Pages\CreateUnitOfMeasure;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Party;
use App\Models\PartyContact;
use App\Models\Project;
use App\Models\ProjectSite;
use App\Models\TaxCode;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OperationalMasterDataTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_one_party_can_hold_multiple_roles_and_only_one_contact_remains_primary(): void
    {
        $company = Company::factory()->create();
        $party = Party::factory()->forCompany($company)->create([
            'roles' => [
                PartyRole::Customer->value,
                PartyRole::Vendor->value,
                PartyRole::Customer->value,
            ],
        ]);

        $firstContact = PartyContact::factory()->create([
            'company_id' => $company->getKey(),
            'party_id' => $party->getKey(),
            'is_primary' => true,
        ]);
        $secondContact = PartyContact::factory()->create([
            'company_id' => $company->getKey(),
            'party_id' => $party->getKey(),
            'is_primary' => true,
        ]);

        $this->assertSame(
            [PartyRole::Customer->value, PartyRole::Vendor->value],
            $party->roles,
        );
        $this->assertFalse($firstContact->fresh()->is_primary);
        $this->assertTrue($secondContact->fresh()->is_primary);
    }

    public function test_party_requires_at_least_one_valid_role(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('valid party role');

        Party::factory()->create(['roles' => []]);
    }

    public function test_projects_require_same_company_customer_and_consultant_parties(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $client = Party::factory()->forCompany($company)->withRoles(PartyRole::Customer)->create();
        $consultant = Party::factory()->forCompany($company)->withRoles(PartyRole::Consultant)->create();
        $otherClient = Party::factory()->forCompany($otherCompany)->withRoles(PartyRole::Customer)->create();

        $project = Project::factory()->create([
            'company_id' => $company->getKey(),
            'client_party_id' => $client->getKey(),
            'consultant_party_id' => $consultant->getKey(),
            'status' => ProjectStatus::Active,
        ]);

        $this->assertTrue($project->client->is($client));
        $this->assertTrue($project->consultant->is($consultant));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('same company');

        Project::factory()->create([
            'company_id' => $company->getKey(),
            'client_party_id' => $otherClient->getKey(),
        ]);
    }

    public function test_items_and_sites_reject_cross_company_relationships(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $category = ItemCategory::factory()->create(['company_id' => $company->getKey()]);
        $otherUnit = UnitOfMeasure::factory()->create(['company_id' => $otherCompany->getKey()]);

        try {
            Item::factory()->create([
                'company_id' => $company->getKey(),
                'item_category_id' => $category->getKey(),
                'unit_of_measure_id' => $otherUnit->getKey(),
            ]);
            $this->fail('Cross-company unit of measure was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('same company', $exception->getMessage());
        }

        $project = Project::factory()->create(['company_id' => $company->getKey()]);
        $otherCostCenter = CostCenter::factory()->create(['company_id' => $otherCompany->getKey()]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('same company');

        ProjectSite::factory()->create([
            'company_id' => $company->getKey(),
            'project_id' => $project->getKey(),
            'cost_center_id' => $otherCostCenter->getKey(),
        ]);
    }

    public function test_services_cannot_track_inventory(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Services cannot track inventory');

        Item::factory()->create([
            'type' => ItemType::Service,
            'track_inventory' => true,
        ]);
    }

    public function test_tax_codes_are_inactive_by_default_and_effective_versions_cannot_overlap(): void
    {
        $company = Company::factory()->create();
        $taxCode = TaxCode::factory()->create([
            'company_id' => $company->getKey(),
            'code' => 'GST-SYNTHETIC',
            'type' => TaxCodeType::SalesTax,
            'calculation_method' => TaxCalculationMethod::Exclusive,
            'effective_from' => '2026-07-01',
            'effective_to' => '2026-12-31',
        ]);

        $this->assertFalse($taxCode->is_active);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot overlap');

        TaxCode::factory()->create([
            'company_id' => $company->getKey(),
            'code' => 'GST-SYNTHETIC',
            'effective_from' => '2026-12-01',
            'effective_to' => null,
        ]);
    }

    public function test_operational_resources_are_tenant_isolated_and_permission_protected(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $currentParty = Party::factory()->forCompany($company)->create();
        $otherParty = Party::factory()->forCompany($otherCompany)->create();
        $currentProject = Project::factory()->create(['company_id' => $company->getKey()]);
        $otherProject = Project::factory()->create(['company_id' => $otherCompany->getKey()]);
        $currentCostCenter = CostCenter::factory()->create(['company_id' => $company->getKey()]);
        $otherCostCenter = CostCenter::factory()->create(['company_id' => $otherCompany->getKey()]);
        $currentItem = Item::factory()->create(['company_id' => $company->getKey()]);
        $otherItem = Item::factory()->create(['company_id' => $otherCompany->getKey()]);
        $user = $this->userWithCompany($company, [
            'ViewAny:Party',
            'ViewAny:Project',
            'ViewAny:CostCenter',
            'ViewAny:Item',
        ]);

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        $this->assertContains($currentParty->getKey(), PartyResource::getEloquentQuery()->pluck('id')->all());
        $this->assertContains($currentProject->getKey(), ProjectResource::getEloquentQuery()->pluck('id')->all());
        $this->assertContains($currentCostCenter->getKey(), CostCenterResource::getEloquentQuery()->pluck('id')->all());
        $this->assertContains($currentItem->getKey(), ItemResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($otherParty->getKey(), PartyResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($otherProject->getKey(), ProjectResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($otherCostCenter->getKey(), CostCenterResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($otherItem->getKey(), ItemResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_operational_master_data_create_forms_render_for_authorized_tenant_user(): void
    {
        $company = Company::factory()->create();
        $subjects = [
            'Party',
            'CostCenter',
            'UnitOfMeasure',
            'ItemCategory',
            'TaxCode',
            'Item',
            'Project',
            'ProjectSite',
            'ProjectBudget',
        ];
        $user = $this->userWithCompany(
            $company,
            collect($subjects)
                ->flatMap(fn (string $subject): array => [
                    "ViewAny:{$subject}",
                    "Create:{$subject}",
                ])
                ->all(),
        );

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();

        foreach ([
            CreateParty::class,
            CreateCostCenter::class,
            CreateUnitOfMeasure::class,
            CreateItemCategory::class,
            CreateTaxCode::class,
            CreateItem::class,
            CreateProject::class,
            CreateProjectSite::class,
            CreateProjectBudget::class,
        ] as $page) {
            Livewire::test($page)->assertSuccessful();
        }
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithCompany(Company $company, array $permissions): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);
        $user->givePermissionTo(
            collect($permissions)
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission))
                ->all(),
        );

        return $user;
    }
}
