<?php

namespace Tests\Feature\Filament;

use App\Enums\PayrollRunStatus;
use App\Filament\Resources\PayrollRuns\Pages\CreatePayrollRun;
use App\Filament\Resources\PayrollRuns\Pages\ListPayrollRuns;
use App\Filament\Resources\PayrollRuns\Pages\ViewPayrollRun;
use App\Models\Company;
use App\Models\PayrollRun;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayrollAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_list_and_create_are_company_scoped(): void
    {
        $company = Company::factory()->create();
        $other = Company::factory()->create();
        $run = PayrollRun::factory()->create(['company_id' => $company->getKey()]);
        $otherRun = PayrollRun::factory()->create(['company_id' => $other->getKey()]);
        $user = User::factory()->create();
        $this->authenticate($user, $company, ['ViewAny:PayrollRun', 'View:PayrollRun', 'Create:PayrollRun']);

        Livewire::test(ListPayrollRuns::class)->assertCanSeeTableRecords([$run])->assertCanNotSeeTableRecords([$otherRun]);
        Livewire::test(CreatePayrollRun::class)->fillForm([
            'reference_number' => 'PAY-2026-08',
            'currency_code' => 'PKR',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ])->call('create')->assertHasNoFormErrors();

        $this->assertTrue(PayrollRun::query()->where('reference_number', 'PAY-2026-08')->sole()->company->is($company));
    }

    public function test_each_workflow_action_requires_its_permission_and_state(): void
    {
        $company = Company::factory()->create();
        $run = PayrollRun::factory()->create(['company_id' => $company->getKey()]);
        $user = User::factory()->create();
        $this->authenticate($user, $company, ['ViewAny:PayrollRun', 'View:PayrollRun']);

        Livewire::test(ViewPayrollRun::class, ['record' => $run->getRouteKey()])
            ->assertActionHidden('generateEntries')->assertActionHidden('submit')->assertActionHidden('approve');

        $user->givePermissionTo([
            Permission::findOrCreate('GenerateEntries:PayrollRun'),
            Permission::findOrCreate('Submit:PayrollRun'),
        ]);
        Livewire::test(ViewPayrollRun::class, ['record' => $run->getRouteKey()])
            ->assertActionVisible('generateEntries')->assertActionVisible('submit')->assertActionHidden('approve');

        $run->update(['status' => PayrollRunStatus::UnderReview]);
        $user->givePermissionTo(Permission::findOrCreate('Approve:PayrollRun'));
        Livewire::test(ViewPayrollRun::class, ['record' => $run->getRouteKey()])
            ->assertActionVisible('approve')->assertActionHidden('submit');
    }

    /** @param array<int, string> $permissions */
    private function authenticate(User $user, Company $company, array $permissions): void
    {
        $user->companies()->attach($company, ['is_active' => true, 'can_access_descendants' => false]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }
        $this->actingAs($user);
        Filament::setTenant($company);
        Filament::bootCurrentPanel();
    }
}
