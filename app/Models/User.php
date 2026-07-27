<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'email_verified_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['is_active', 'can_access_descendants'])
            ->withTimestamps();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * @return Collection<int, Company>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->getAccessibleCompanies();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant instanceof Company
            && $tenant->is_active
            && $this->getAccessibleCompanies()->contains(fn (Company $company): bool => $company->is($tenant));
    }

    /**
     * @return Collection<int, Company>
     */
    public function getAccessibleCompanies(): Collection
    {
        if ($this->hasRole('super_admin')) {
            return Company::query()
                ->active()
                ->orderBy('name')
                ->get();
        }

        $directCompanies = $this->companies()
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->orderBy('companies.name')
            ->get();

        $descendantRootIds = $directCompanies
            ->filter(fn (Company $company): bool => (bool) $company->pivot->can_access_descendants)
            ->modelKeys();

        if ($descendantRootIds === []) {
            return $directCompanies;
        }

        $allActiveCompanies = Company::query()
            ->active()
            ->get()
            ->keyBy(fn (Company $company): int => $company->getKey());

        $descendantCompanies = $allActiveCompanies->filter(
            function (Company $company) use ($allActiveCompanies, $descendantRootIds): bool {
                $parentCompanyId = $company->parent_company_id;
                $visitedCompanyIds = [];

                while ($parentCompanyId !== null) {
                    if (in_array($parentCompanyId, $descendantRootIds, true)) {
                        return true;
                    }

                    if (in_array($parentCompanyId, $visitedCompanyIds, true)) {
                        return false;
                    }

                    $visitedCompanyIds[] = $parentCompanyId;
                    $parentCompanyId = $allActiveCompanies->get($parentCompanyId)?->parent_company_id;
                }

                return false;
            }
        );

        return $directCompanies
            ->concat($descendantCompanies)
            ->unique(fn (Company $company): int => $company->getKey())
            ->sortBy('name')
            ->values();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
