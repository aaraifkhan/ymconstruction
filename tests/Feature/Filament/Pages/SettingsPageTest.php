<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\Settings;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    public function test_settings_form_uses_the_filament_schema_tabs_component(): void
    {
        $page = app(Settings::class);

        $components = $page->form(Schema::make($page))->getComponents();

        $this->assertCount(1, $components);
        $this->assertInstanceOf(Tabs::class, $components[0]);
        $this->assertSame(
            ['General'],
            array_map(
                static fn (Tab $tab): string => $tab->getLabel(),
                $components[0]->getChildSchema()->getComponents(),
            ),
        );

        $generalSections = $components[0]
            ->getChildSchema()
            ->getComponents()[0]
            ->getChildSchema()
            ->getComponents();

        $this->assertContainsOnlyInstancesOf(Section::class, $generalSections);
        $this->assertSame(
            ['Brand identity', 'Brand logo'],
            array_map(
                static fn (Section $section): string => $section->getHeading(),
                $generalSections,
            ),
        );
    }
}
