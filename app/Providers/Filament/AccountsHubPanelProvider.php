<?php

namespace App\Providers\Filament;

use App\Filament\Pages\GeneralAssetCustodyPage;
use App\Filament\Pages\GeneralGroupExpensePage;
use App\Filament\Pages\MasterAccountsHubPage;
use App\Filament\Pages\MyProfile;
use App\Filament\Pages\QuickExpenseEntryPage;
use App\Filament\Pages\SharedCostAllocationPage;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;
use Jeffgreco13\FilamentBreezy\BreezyCore;

class AccountsHubPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $primaryColor = Color::Teal;
        $brandName = 'YMC Group - Accounts Hub & Fast Entry';
        $brandLogo = null;
        $favicon = asset('images/favicon.svg');

        try {
            $settings = app(GeneralSettings::class);
            if (! empty($settings->primary_color)) {
                $primaryColor = Color::hex($settings->primary_color);
            }
            if (! empty($settings->brand_name)) {
                $brandName = $settings->brand_name.' - Accounts Hub';
            }
            if (! empty($settings->brand_logo)) {
                $brandLogo = asset('storage/'.$settings->brand_logo);
                $favicon = $brandLogo;
            }
        } catch (\Throwable $e) {
            // Fallback during setup
        }

        $panel
            ->id('accounts-hub')
            ->path('accounts-hub')
            ->login()
            ->homeUrl(fn (): string => route('portal'))
            ->colors([
                'primary' => $primaryColor,
            ])
            ->brandName($brandName)
            ->favicon($favicon)
            ->assets([
                Css::make('profile-page', resource_path('css/filament/admin/profile-page.css')),
                Css::make('sidebar-user-menu', resource_path('css/filament/admin/sidebar-user-menu.css')),
            ])
            ->pages([
                MasterAccountsHubPage::class,
                QuickExpenseEntryPage::class,
                GeneralGroupExpensePage::class,
                GeneralAssetCustodyPage::class,
                SharedCostAllocationPage::class,
            ])
            ->navigation(function (): NavigationBuilder {
                $builder = new NavigationBuilder;

                $builder->groups([
                    NavigationGroup::make('Accounts Hub & Fast Entry')
                        ->items([
                            ...MasterAccountsHubPage::getNavigationItems(),
                            ...QuickExpenseEntryPage::getNavigationItems(),
                            ...GeneralGroupExpensePage::getNavigationItems(),
                            ...GeneralAssetCustodyPage::getNavigationItems(),
                            ...SharedCostAllocationPage::getNavigationItems(),
                        ]),

                    NavigationGroup::make('Portal')
                        ->items([
                            NavigationItem::make('Back to Access Portal')
                                ->url(fn (): string => route('portal'))
                                ->icon('heroicon-o-arrow-left-on-rectangle'),
                        ]),
                ]);

                return $builder;
            })
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): View => view('filament.admin.sidebar-user-menu'),
            )
            ->userMenuItems([
                Action::make('portal')
                    ->label('Access Portal')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->url(fn (): string => route('portal')),
                'profile' => fn (Action $action): Action => $action
                    ->url(fn (): ?string => MyProfile::getUrl())
                    ->visible(fn (): bool => MyProfile::canAccess()),
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->scopeToTenant(false),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: false,
                        shouldRegisterNavigation: false,
                        hasAvatars: true,
                        slug: 'my-profile',
                        navigationGroup: 'Settings',
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

        if ($brandLogo) {
            $panel->brandLogo($brandLogo);
        }

        return $panel;
    }
}
