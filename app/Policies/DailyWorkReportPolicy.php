<?php

namespace App\Policies;

use App\Models\DailyWorkReport;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\HandlesAuthorization;

class DailyWorkReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:DailyWorkReport') && $this->canAccessCurrentCompany($user);
    }

    public function view(User $user, DailyWorkReport $report): bool
    {
        if (! $user->canAccessTenant($report->company)) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('department_head') || $this->hasPermission($user, 'ViewAll:DailyWorkReport')) {
            return true;
        }

        $employmentId = $user->employee?->employments()->where('company_id', $report->company_id)->value('id');

        if ($employmentId && (int) $report->employment_id === (int) $employmentId) {
            return true;
        }

        // Team Lead view
        if ($report->department_team_id && $employmentId) {
            $isLead = $report->departmentTeam?->team_lead_id === $employmentId;
            if ($isLead) {
                return true;
            }
        }

        return $this->hasPermission($user, 'View:DailyWorkReport');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:DailyWorkReport') && $this->canAccessCurrentCompany($user);
    }

    public function update(User $user, DailyWorkReport $report): bool
    {
        if (! $user->canAccessTenant($report->company)) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('department_head')) {
            return true;
        }

        $employmentId = $user->employee?->employments()->where('company_id', $report->company_id)->value('id');

        // Member can update their own un-reviewed report for today
        if ($employmentId && (int) $report->employment_id === (int) $employmentId) {
            return true;
        }

        return $this->hasPermission($user, 'Update:DailyWorkReport');
    }

    public function delete(User $user, DailyWorkReport $report): bool
    {
        return $this->hasPermission($user, 'Delete:DailyWorkReport') && $user->canAccessTenant($report->company);
    }

    public function restore(User $user, DailyWorkReport $report): bool
    {
        return $this->hasPermission($user, 'Restore:DailyWorkReport') && $user->canAccessTenant($report->company);
    }

    public function forceDelete(User $user, DailyWorkReport $report): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:DailyWorkReport') && $this->canAccessCurrentCompany($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function canAccessCurrentCompany(User $user): bool
    {
        $company = Filament::getTenant();

        return $company !== null && $user->canAccessTenant($company);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasRole('super_admin') || $user->can($permission);
    }
}
