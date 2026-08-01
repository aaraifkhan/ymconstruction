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

        return view('portal.index', [
            'companies' => $user->getAccessibleCompanies(),
            'isSuperAdmin' => $user->hasRole('super_admin'),
        ]);
    }

    public function enterCompany(Request $request, Company $company): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($company->is_active && $user->canAccessTenant($company), 403);

        return redirect()->to(Filament::getPanel('admin')->getUrl($company));
    }

    public function superAdmin(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->hasRole('super_admin'), 403);

        return view('super-admin.index', [
            'companies' => $user->getAccessibleCompanies(),
        ]);
    }
}
