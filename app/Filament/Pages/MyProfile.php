<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;

class MyProfile extends MyProfilePage
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Profile';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function getNavigationIcon(): string|\BackedEnum|Htmlable|null
    {
        return 'heroicon-o-user-circle';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    protected string $view = 'filament-panels::pages.page';

    public function getSubheading(): string
    {
        return 'Manage your personal details, password, and account security.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Profile settings')
                    ->persistTabInQueryString('profile-tab')
                    ->tabs($this->getProfileTabs()),
            ]);
    }

    /**
     * @return array<Tab>
     */
    private function getProfileTabs(): array
    {
        $tabs = [];

        foreach ($this->getRegisteredMyProfileComponents() as $key => $component) {
            $metadata = $this->getProfileTabMetadata($key);

            $tabs[] = Tab::make($metadata['label'])
                ->icon($metadata['icon'])
                ->schema([
                    Livewire::make($component)
                        ->key("profile-{$key}"),
                ]);
        }

        return $tabs;
    }

    /**
     * @return array{label: string, icon: string}
     */
    private function getProfileTabMetadata(string $key): array
    {
        return match ($key) {
            'personal_info' => [
                'label' => 'Personal Information',
                'icon' => 'heroicon-o-user-circle',
            ],
            'update_password' => [
                'label' => 'Password',
                'icon' => 'heroicon-o-key',
            ],
            'two_factor_authentication' => [
                'label' => 'Two-Factor Authentication',
                'icon' => 'heroicon-o-shield-check',
            ],
            'browser_sessions' => [
                'label' => 'Browser Sessions',
                'icon' => 'heroicon-o-computer-desktop',
            ],
            'passkeys' => [
                'label' => 'Passkeys',
                'icon' => 'heroicon-o-finger-print',
            ],
            'sanctum_tokens' => [
                'label' => 'API Tokens',
                'icon' => 'heroicon-o-code-bracket',
            ],
            default => [
                'label' => Str::headline($key),
                'icon' => 'heroicon-o-adjustments-horizontal',
            ],
        };
    }
}
