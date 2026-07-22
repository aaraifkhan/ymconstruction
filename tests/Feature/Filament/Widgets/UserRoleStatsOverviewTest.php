<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\UserRoleStatsOverview;
use App\Models\User;
use Filament\Enums\UserMenuPosition;
use Filament\Facades\Filament;
use Filament\Livewire\Sidebar;
use Filament\Widgets\AccountWidget;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleStatsOverviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_widget_requires_its_shield_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertFalse(UserRoleStatsOverview::canView());

        $user->givePermissionTo(Permission::findOrCreate('View:UserRoleStatsOverview'));

        $this->assertTrue(UserRoleStatsOverview::canView());
    }

    public function test_widget_displays_user_and_role_totals(): void
    {
        User::factory()->count(3)->create();
        Role::create(['name' => 'Manager']);
        Role::create(['name' => 'Supervisor']);

        Livewire::test(UserRoleStatsOverview::class)
            ->assertSeeInOrder([
                'Total Users',
                '3',
                'Total Roles',
                '2',
            ]);
    }

    public function test_profile_menu_stays_in_topbar_and_account_widget_is_not_on_the_dashboard(): void
    {
        $panel = Filament::getCurrentPanel();

        $this->assertSame(UserMenuPosition::Topbar, $panel->getUserMenuPosition());
        $this->assertNotContains(AccountWidget::class, $panel->getWidgets());
    }

    public function test_sidebar_footer_renders_the_default_filament_user_menu(): void
    {
        $user = User::factory()->create(['name' => 'Aaraif Hanif']);

        $this->actingAs($user);

        Filament::getCurrentPanel()->boot();

        $sidebar = Livewire::test(Sidebar::class)
            ->assertSee('Aaraif Hanif')
            ->assertSee(filament()->getLogoutUrl())
            ->assertSee('fi-sidebar-user-menu-logout', false)
            ->assertSee('method="post"', false)
            ->assertSee('fi-icon-btn', false);

        $this->assertGreaterThan(0, substr_count($sidebar->html(), 'fi-user-menu'));
    }
}
