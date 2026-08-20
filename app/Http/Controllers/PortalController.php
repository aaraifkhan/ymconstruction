<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $isSuperAdmin = $user->hasRole('super_admin');
        $hasAccountsHubAccess = $isSuperAdmin
            || $user->can('View:MasterAccountsHub')
            || $user->can('Create:JournalEntry')
            || ($user->getAccessibleCompanies()->isNotEmpty() && $user->getAccessibleCompanies()->contains(fn (Company $c) => $c->hasModuleEnabled('accounts')));

        return view('portal.index', [
            'companies' => $user->getAccessibleCompanies(),
            'isSuperAdmin' => $isSuperAdmin,
            'hasAccountsHubAccess' => $hasAccountsHubAccess,
        ]);
    }

    public function enterCompany(Request $request, Company $company): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($company->is_active && $user->canAccessTenant($company), 403);

        return redirect()->to(Filament::getPanel('admin')->getUrl($company));
    }

    public function superAdmin(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->hasRole('super_admin'), 403);

        return redirect()->to(Filament::getPanel('super-admin')->getUrl());
    }

    public function accountsHub(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canAccess = $user->hasRole('super_admin')
            || $user->can('View:MasterAccountsHub')
            || $user->can('Create:JournalEntry')
            || ($user->getAccessibleCompanies()->isNotEmpty() && $user->getAccessibleCompanies()->contains(fn (Company $c) => $c->hasModuleEnabled('accounts')));

        abort_unless($canAccess, 403);

        return redirect()->to(Filament::getPanel('accounts-hub')->getUrl());
    }
}
