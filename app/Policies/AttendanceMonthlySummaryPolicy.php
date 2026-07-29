<?php

namespace App\Policies;

use App\Models\AttendanceMonthlySummary;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendanceMonthlySummaryPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AttendanceMonthlySummary';

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }

    public function generate(User $user, AttendanceMonthlySummary $summary): bool
    {
        return $this->hasPermission($user, 'Generate:AttendanceMonthlySummary') && $this->canAccessRecord($user, $summary);
    }

    public function finalize(User $user, AttendanceMonthlySummary $summary): bool
    {
        return $this->hasPermission($user, 'Finalize:AttendanceMonthlySummary') && $this->canAccessRecord($user, $summary);
    }
}
