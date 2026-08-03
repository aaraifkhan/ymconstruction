<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_regular_user_sees_only_authorized_company_cards(): void
    {
        $this->seed(CompanySeeder::class);
        $bmc = Company::query()->where('slug', 'bmc-construction')->firstOrFail();
        $ymc = Company::query()->where('slug', 'ymc-construction')->firstOrFail();
        $user = User::factory()->create();
        $user->companies()->attach([
            $bmc->getKey() => ['is_active' => true, 'can_access_descendants' => false],
            $ymc->getKey() => ['is_active' => true, 'can_access_descendants' => false],
        ]);

        $this->actingAs($user)
            ->get('/portal')
            ->assertOk()
            ->assertSee('BMC Construction')
            ->assertSee('YMC Construction')
            ->assertDontSee('7 Orbit Medical Billing')
            ->assertDontSee('Super Admin');
    }

    public function test_successful_filament_logins_always_redirect_to_the_access_portal(): void
    {
        $response = app(LoginResponseContract::class)->toResponse(request());

        $this->assertSame(route('portal'), $response->getTargetUrl());
    }

    public function test_livewire_login_redirects_to_access_portal(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertRedirect(route('portal'));
    }

    public function test_super_admin_sees_four_company_cards_and_super_admin_card(): void
    {
        $this->seed(CompanySeeder::class);
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user)
            ->get('/portal')
            ->assertOk()
            ->assertSee('BMC Construction')
            ->assertSee('YMC Construction')
            ->assertSee('7 Orbit')
            ->assertSee('7 Orbit Medical Billing')
            ->assertSee('Super Admin')
            ->assertSee('images/company-logos/bmc-construction.png')
            ->assertSee('images/company-logos/ymc-construction.png')
            ->assertSee('images/company-logos/7-orbit.png')
            ->assertSee('images/company-logos/7-orbit-medical-billing.png');
    }

    public function test_company_card_rechecks_membership_before_entering_company_operations(): void
    {
        $this->seed(CompanySeeder::class);
        $company = Company::query()->where('slug', 'bmc-construction')->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('portal.company', $company))
            ->assertForbidden();
    }

    public function test_super_admin_entry_is_denied_to_regular_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('portal.super-admin'))
            ->assertForbidden();
    }

    public function test_super_admin_can_open_the_non_tenant_admin_landing(): void
    {
        $this->seed(CompanySeeder::class);
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Collective administration and reporting')
            ->assertSee('BMC Construction')
            ->assertSee('7 Orbit Medical Billing');
    }

    public function test_unauthenticated_guests_redirect_to_login_when_accessing_admin(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_unauthenticated_guests_redirect_to_login_when_accessing_portal(): void
    {
        $this->get('/portal')
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_login_route_redirects_to_filament_login(): void
    {
        $this->get(route('login'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }
}
