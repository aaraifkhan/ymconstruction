<?php

namespace App\Providers;

use App\Models\CompanyBankAccount;
use App\Observers\CompanyBankAccountObserver;
use App\Policies\ActivityPolicy;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CompanyBankAccount::observe(CompanyBankAccountObserver::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
