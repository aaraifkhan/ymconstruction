<?php

namespace App\Providers\Filament;

use App\Filament\Pages\MyProfile;
use App\Filament\Pages\Tenancy\RegisterCompany;
use App\Models\Company;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;
use Jeffgreco13\FilamentBreezy\BreezyCore;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $primaryColor = Color::Amber;
        $brandName = 'YM Construction';
        $brandLogo = null;
        $favicon = asset('images/favicon.svg');

        try {
            $settings = app(GeneralSettings::class);
            if (! empty($settings->primary_color)) {
                // Determine if it's hex, rgb, or just use the color directly
                $primaryColor = Color::hex($settings->primary_color);
            }
            if (! empty($settings->brand_name)) {
                $brandName = $settings->brand_name;
            }
            if (! empty($settings->brand_logo)) {
                $brandLogo = asset('storage/'.$settings->brand_logo);
                $favicon = $brandLogo;
            }
        } catch (\Throwable $e) {
            // Safe fallback during migrations
        }

        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->tenant(Company::class, slugAttribute: 'slug')
            ->tenantRoutePrefix('company')
            ->tenantRegistration(RegisterCompany::class)
            ->searchableTenantMenu()
            ->colors([
                'primary' => $primaryColor,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->assets([
                Css::make('profile-page', resource_path('css/filament/admin/profile-page.css')),
                Css::make('sidebar-user-menu', resource_path('css/filament/admin/sidebar-user-menu.css')),
            ])
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): View => view('filament.admin.sidebar-user-menu'),
            )
            ->navigationGroups([
                'Master Data',
                'Accounting',
                'Reports',
                'Company Management',
                'User Management',
                'System',
                'Settings',
            ])
            ->brandName($brandName)
            ->favicon($favicon)
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action
                    ->url(fn (): ?string => Filament::getTenant() !== null ? MyProfile::getUrl() : null)
                    ->visible(fn (): bool => Filament::getTenant() !== null && MyProfile::canAccess()),
            ]);

        if ($brandLogo) {
            $panel->brandLogo($brandLogo);
        }

        return $panel
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->scopeToTenant(false),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: false,
                        shouldRegisterNavigation: false,
                        hasAvatars: true,
                        slug: 'my-profile'
                    )
                    ->customMyProfilePage(MyProfile::class)
                    ->enableTwoFactorAuthentication(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
