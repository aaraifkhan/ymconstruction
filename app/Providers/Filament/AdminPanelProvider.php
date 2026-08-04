<?php

namespace App\Providers\Filament;

use App\Filament\Pages\MyProfile;
use App\Models\Company;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Facades\Filament;
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
        $brandName = 'YMC Group Management';
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
            ->searchableTenantMenu()
            ->homeUrl(fn (): string => route('portal'))
            ->colors([
                'primary' => $primaryColor,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([])
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
                'Administration',
                'Assets',
                'Transactions',
                'Document Management',
                'Approvals',
                'HR Management',
            ])
            ->navigation(function () use ($panel): NavigationBuilder {
                $builder = new NavigationBuilder;

                if (Filament::getTenant() === null) {
                    return $builder;
                }

                $hrParents = [
                    'HR Management' => NavigationItem::make('HR Management')
                        ->icon('heroicon-o-user-group')
                        ->group('HR Management'),
                    'Attendance & Leave' => NavigationItem::make('Attendance & Leave')
                        ->icon('heroicon-o-clock')
                        ->group('HR Management'),
                    'Payroll' => NavigationItem::make('Payroll')
                        ->icon('heroicon-o-banknotes')
                        ->group('HR Management'),
                    'Loans & Advances' => NavigationItem::make('Loans & Advances')
                        ->icon('heroicon-o-credit-card')
                        ->group('HR Management'),
                    'Reports' => NavigationItem::make('Reports')
                        ->icon('heroicon-o-document-chart-bar')
                        ->group('HR Management'),
                    'HR System & Migration' => NavigationItem::make('HR System & Migration')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->group('HR Management'),
                ];

                $hrGroupLabels = ['HR Management', 'Attendance & Leave', 'Payroll', 'Loans & Advances', 'HR Configuration', 'HR'];
                $hrReportLabels = ['Group HR', 'HR Readiness', 'HR Reports & Dashboard', 'Payroll & Advances', 'Final Settlements'];

                $groupItems = [];
                $parentChildren = [
                    'HR Management' => [],
                    'Attendance & Leave' => [],
                    'Payroll' => [],
                    'Loans & Advances' => [],
                    'Reports' => [],
                    'HR System & Migration' => [],
                ];

                foreach ($panel->getResources() as $resource) {
                    if (! method_exists($resource, 'getNavigationItems')) {
                        continue;
                    }
                    try {
                        $items = $resource::getNavigationItems();
                    } catch (\Throwable $e) {
                        continue;
                    }
                    foreach ($items as $item) {
                        if (! $item->isVisible()) {
                            continue;
                        }

                        $group = $item->getGroup() ?? 'Other';

                        if (in_array($group, $hrGroupLabels)) {
                            if ($group === 'Attendance & Leave') {
                                $targetParent = 'Attendance & Leave';
                            } elseif ($group === 'Payroll') {
                                $targetParent = 'Payroll';
                            } elseif ($group === 'Loans & Advances') {
                                $targetParent = 'Loans & Advances';
                            } elseif ($group === 'HR') {
                                $targetParent = 'HR System & Migration';
                            } else {
                                $targetParent = 'HR Management';
                            }

                            $item->parentItem($targetParent)->group('HR Management');
                            $parentChildren[$targetParent][] = $item;
                        } else {
                            $groupItems[$group][] = $item;
                        }
                    }
                }

                foreach ($panel->getPages() as $page) {
                    if (! method_exists($page, 'getNavigationItems')) {
                        continue;
                    }
                    try {
                        $items = $page::getNavigationItems();
                    } catch (\Throwable $e) {
                        continue;
                    }
                    foreach ($items as $item) {
                        if (! $item->isVisible()) {
                            continue;
                        }

                        $group = $item->getGroup() ?? 'Other';
                        $label = $item->getLabel();

                        if (in_array($group, $hrGroupLabels) || in_array($label, $hrReportLabels)) {
                            if ($label === 'HR Readiness') {
                                $targetParent = 'HR System & Migration';
                            } elseif (in_array($label, $hrReportLabels)) {
                                $targetParent = 'Reports';
                            } elseif ($group === 'Attendance & Leave') {
                                $targetParent = 'Attendance & Leave';
                            } elseif ($group === 'Payroll') {
                                $targetParent = 'Payroll';
                            } elseif ($group === 'Loans & Advances') {
                                $targetParent = 'Loans & Advances';
                            } elseif ($group === 'HR') {
                                $targetParent = 'HR System & Migration';
                            } else {
                                $targetParent = 'HR Management';
                            }

                            $item->parentItem($targetParent)->group('HR Management');
                            $parentChildren[$targetParent][] = $item;
                        } else {
                            $groupItems[$group][] = $item;
                        }
                    }
                }

                $activeHrParents = [];
                foreach ($hrParents as $parentName => $parentItem) {
                    $children = $parentChildren[$parentName] ?? [];
                    if (count($children)) {
                        usort($children, fn ($a, $b) => strcmp($a->getLabel(), $b->getLabel()));
                        $parentItem->childItems($children);
                        $activeHrParents[] = $parentItem;
                    }
                }

                $navGroups = [];
                $orderedGroupNames = [
                    'Master Data',
                    'HR Management',
                    'Accounting',
                    'Reports',
                    'Company Management',
                    'User Management',
                    'System',
                    'Settings',
                    'Administration',
                    'Assets',
                    'Transactions',
                    'Document Management',
                    'Approvals',
                ];

                foreach ($orderedGroupNames as $gName) {
                    if ($gName === 'HR Management') {
                        $navGroups[] = NavigationGroup::make('HR Management')->items($activeHrParents);
                    } elseif (isset($groupItems[$gName])) {
                        $items = $groupItems[$gName];
                        usort($items, fn ($a, $b) => strcmp($a->getLabel(), $b->getLabel()));
                        $navGroups[] = NavigationGroup::make($gName)->items($items);
                    }
                }

                $builder->groups($navGroups);

                return $builder;
            })
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
