<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\MyProfile;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyProfilePageTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');

        $panel = Filament::getPanel('admin');

        Filament::setCurrentPanel($panel);
        $panel->getPlugin('filament-breezy')->boot($panel);
        $this->company = Company::factory()->create();
    }

    public function test_profile_page_and_user_menu_item_require_the_page_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAsCompanyUser($user);

        $this->assertFalse(MyProfile::canAccess());
        $this->assertArrayNotHasKey('profile', Filament::getCurrentPanel()->getUserMenuItems());

        $this->get(MyProfile::getUrl())
            ->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate('View:MyProfile'));

        $this->assertTrue(MyProfile::canAccess());
        $this->assertArrayHasKey('profile', Filament::getCurrentPanel()->getUserMenuItems());

        $this->get(MyProfile::getUrl())
            ->assertOk();
    }

    public function test_tenant_registration_page_can_be_rendered_without_url_generation_exception(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('Create:Company'));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->get('/admin/new')
            ->assertOk();
    }

    private function actingAsCompanyUser(User $user): void
    {
        $this->company->members()->attach($user, ['is_active' => true]);

        $this->actingAs($user);
        Filament::setTenant($this->company);
        Filament::bootCurrentPanel();
    }

    public function test_profile_components_are_organized_into_filament_tabs(): void
    {
        $page = app(MyProfile::class);

        $components = $page->content(Schema::make($page))->getComponents();

        $this->assertCount(1, $components);
        $this->assertInstanceOf(Tabs::class, $components[0]);

        $tabs = $components[0]->getChildSchema()->getComponents();

        $this->assertSame(
            ['Personal Information', 'Password', 'Two-Factor Authentication'],
            array_map(
                static fn (Tab $tab): string => $tab->getLabel(),
                $tabs,
            ),
        );

        foreach ($tabs as $tab) {
            $this->assertInstanceOf(
                LivewireComponent::class,
                $tab->getChildSchema()->getComponents()[0],
            );
        }
    }

    public function test_profile_forms_use_filament_section_footers_for_actions(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PersonalInfo::class)
            ->assertSeeHtml('fi-profile-form')
            ->assertSeeHtml('fi-section-footer');

        Livewire::actingAs($user)
            ->test(UpdatePassword::class)
            ->assertSeeHtml('fi-profile-form')
            ->assertSeeHtml('fi-section-footer');
    }

    public function test_two_factor_status_uses_filament_callout_with_a_small_icon(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TwoFactorAuthentication::class)
            ->assertSeeHtml('fi-callout')
            ->assertSeeHtml('fi-size-sm')
            ->assertDontSeeHtml('w-6');
    }
}
