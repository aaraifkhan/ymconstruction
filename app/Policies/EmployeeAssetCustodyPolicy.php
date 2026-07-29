<?php

namespace App\Policies;

use App\Models\EmployeeAssetCustody;
use App\Models\User;

class EmployeeAssetCustodyPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'EmployeeAssetCustody';

    public function issue(User $user, EmployeeAssetCustody $record): bool
    {
        return $this->workflow($user, $record, 'Issue');
    }

    public function acknowledge(User $user, EmployeeAssetCustody $record): bool
    {
        return $this->workflow($user, $record, 'Acknowledge');
    }

    public function transfer(User $user, EmployeeAssetCustody $record): bool
    {
        return $this->workflow($user, $record, 'Transfer');
    }

    public function requestReturn(User $user, EmployeeAssetCustody $record): bool
    {
        return $this->workflow($user, $record, 'RequestReturn');
    }

    public function acceptReturn(User $user, EmployeeAssetCustody $record): bool
    {
        return $this->workflow($user, $record, 'AcceptReturn');
    }

    public function reportException(User $user, EmployeeAssetCustody $record): bool
    {
        return $this->workflow($user, $record, 'ReportException');
    }

    public function resolveException(User $user, EmployeeAssetCustody $record): bool
    {
        return $this->workflow($user, $record, 'ResolveException');
    }

    private function workflow(User $user, EmployeeAssetCustody $record, string $ability): bool
    {
        return $this->hasPermission($user, "{$ability}:EmployeeAssetCustody")
            && $this->canAccessRecord($user, $record);
    }
}
