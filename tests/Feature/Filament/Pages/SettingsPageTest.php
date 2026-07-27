<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\Settings;
use App\Models\User;
use App\Settings\GeneralSettings;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use LazilyRefreshDatabase;

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

    public function test_saving_settings_requires_the_update_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->expectException(AuthorizationException::class);

        app(Settings::class)->save(app(GeneralSettings::class));
    }
}
