<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\MyProfile;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Tests\TestCase;

class MyProfilePageTest extends TestCase
{
    public function test_profile_components_are_organized_into_filament_tabs(): void
    {
        $panel = Filament::getPanel('admin');
        $panel->getPlugin('filament-breezy')->boot($panel);

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
                Livewire::class,
                $tab->getChildSchema()->getComponents()[0],
            );
        }
    }
}
