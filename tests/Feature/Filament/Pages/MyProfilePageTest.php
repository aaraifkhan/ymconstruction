<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\MyProfile;
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
use Tests\TestCase;

class MyProfilePageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $panel = Filament::getPanel('admin');

        Filament::setCurrentPanel($panel);
        $panel->getPlugin('filament-breezy')->boot($panel);
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
