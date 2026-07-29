<?php

namespace App\Policies;

use App\Models\AttendanceImportBatch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendanceImportBatchPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AttendanceImportBatch';

    public function import(User $user, Company $company): bool
    {
        return $this->hasPermission($user, 'Import:AttendanceImportBatch') && $user->canAccessTenant($company);
    }

    public function reprocess(User $user, AttendanceImportBatch $batch): bool
    {
        return $this->hasPermission($user, 'Reprocess:AttendanceImportBatch') && $this->canAccessRecord($user, $batch);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }
}
