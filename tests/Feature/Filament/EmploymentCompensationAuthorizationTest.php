<?php

namespace Tests\Feature\Filament;

use App\Enums\CompensationStatus;
use App\Filament\Resources\EmploymentCompensation\Pages\CreateEmploymentCompensation;
use App\Filament\Resources\EmploymentCompensation\Pages\ListEmploymentCompensation;
use App\Filament\Resources\EmploymentCompensation\Pages\ViewEmploymentCompensation;
use App\Models\Company;
use App\Models\Employment;
use App\Models\EmploymentCompensation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmploymentCompensationAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_list_is_company_scoped(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $compensation = $this->compensationForCompany($company);
        $otherCompensation = $this->compensationForCompany($otherCompany);
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:EmploymentCompensation',
            'View:EmploymentCompensation',
        ]);

        Livewire::test(ListEmploymentCompensation::class)
            ->assertCanSeeTableRecords([$compensation])
            ->assertCanNotSeeTableRecords([$otherCompensation]);
    }

    public function test_create_page_assigns_company_and_actor(): void
    {
        $company = Company::factory()->create();
        $employment = Employment::factory()->forCompany($company)->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:EmploymentCompensation',
            'Create:EmploymentCompensation',
            'ManageAmounts:EmploymentCompensation',
        ]);

        Livewire::test(CreateEmploymentCompensation::class)
            ->fillForm([
                'employment_id' => $employment->getKey(),
                'effective_from' => '2026-08-01',
                'basic_salary' => 100000,
                'house_travel_allowance' => 15000,
                'food_allowance' => 10000,
                'other_allowance' => 5000,
                'currency_code' => 'PKR',
                'notes' => 'Initial package.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $compensation = EmploymentCompensation::query()->sole();

        $this->assertTrue($compensation->company->is($company));
        $this->assertSame($user->getKey(), $compensation->created_by_id);
        $this->assertSame(130000.0, $compensation->grossSalary());
    }

    public function test_workflow_actions_require_individual_permissions_and_state(): void
    {
        $company = Company::factory()->create();
        $compensation = $this->compensationForCompany($company);
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'ViewAny:EmploymentCompensation',
            'View:EmploymentCompensation',
        ]);

        Livewire::test(ViewEmploymentCompensation::class, ['record' => $compensation->getRouteKey()])
            ->assertActionHidden('submit')
            ->assertActionHidden('approve')
            ->assertActionHidden('reject');

        $user->givePermissionTo(Permission::findOrCreate('Submit:EmploymentCompensation'));

        Livewire::test(ViewEmploymentCompensation::class, ['record' => $compensation->getRouteKey()])
            ->assertActionVisible('submit')
            ->assertActionHidden('approve');

        $compensation->update(['status' => CompensationStatus::PendingApproval]);
        $user->givePermissionTo([
            Permission::findOrCreate('Approve:EmploymentCompensation'),
            Permission::findOrCreate('Reject:EmploymentCompensation'),
        ]);

        Livewire::test(ViewEmploymentCompensation::class, ['record' => $compensation->getRouteKey()])
            ->assertActionVisible('approve')
            ->assertActionVisible('reject')
            ->assertActionHidden('submit');
    }

    public function test_create_requires_both_create_and_manage_amount_permissions(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $this->authenticateCompanyUser($user, $company, [
            'Create:EmploymentCompensation',
        ]);

        $this->assertFalse($user->can('create', EmploymentCompensation::class));

        $user->givePermissionTo(Permission::findOrCreate('ManageAmounts:EmploymentCompensation'));

        $this->assertTrue($user->can('create', EmploymentCompensation::class));
    }

    private function compensationForCompany(Company $company): EmploymentCompensation
    {
        $employment = Employment::factory()->forCompany($company)->create();

        return EmploymentCompensation::factory()->create([
            'company_id' => $company->getKey(),
            'employment_id' => $employment->getKey(),
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function authenticateCompanyUser(User $user, Company $company, array $permissions): void
    {
        $user->companies()->attach($company, [
            'is_active' => true,
            'can_access_descendants' => false,
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }

        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
