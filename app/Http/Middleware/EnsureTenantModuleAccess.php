<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantModuleAccess
{
    /**
     * Map of Navigation Groups to Module Catalog Keys
     */
    protected const GROUP_MODULE_MAP = [
        'SM Department Operations' => 'sm_department_operations',
        'HR Management' => 'hr',
        'Accounts Management' => 'accounts',
        'Document Management' => 'documents',
        'Projects Management' => 'projects',
        'Projects' => 'projects',
        'Medical Billing' => 'medical_billing',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if (! ($tenant instanceof Company)) {
            return $next($request);
        }

        $pageOrResource = $this->resolvePageOrResourceClass($request);

        if ($pageOrResource) {
            $group = null;
            if (is_subclass_of($pageOrResource, Page::class) || is_subclass_of($pageOrResource, Resource::class)) {
                $group = $pageOrResource::getNavigationGroup();
            }

            if ($group) {
                $moduleKey = self::GROUP_MODULE_MAP[$group] ?? null;
                if ($moduleKey && ! $tenant->hasModuleEnabled($moduleKey)) {
                    abort(403, "The {$group} module is disabled for {$tenant->name}.");
                }
            }
        }

        return $next($request);
    }

    protected function resolvePageOrResourceClass(Request $request): ?string
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        $controller = $route->getController();
        if ($controller) {
            $class = get_class($controller);
            if (is_subclass_of($class, Page::class)) {
                if (method_exists($class, 'getResource') && $class::getResource()) {
                    return $class::getResource();
                }

                return $class;
            }
            if (is_subclass_of($class, Resource::class)) {
                return $class;
            }
        }

        $action = $route->getAction('controller');
        if (is_string($action) && class_exists($action)) {
            if (is_subclass_of($action, Page::class)) {
                if (method_exists($action, 'getResource') && $action::getResource()) {
                    return $action::getResource();
                }

                return $action;
            }
            if (is_subclass_of($action, Resource::class)) {
                return $action;
            }
        }

        return null;
    }
}
