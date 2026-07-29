<?php

namespace App\Policies;

use App\Models\AttendanceRawEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendanceRawEventPolicy extends CompanyScopedPolicy
{
    protected string $permissionSubject = 'AttendanceRawEvent';

    public function view(User $user, Model $record): bool
    {
        return $this->hasPermission($user, 'ViewRaw:AttendanceRawEvent') && $this->canAccessRecord($user, $record);
    }

    public function viewPayload(User $user, AttendanceRawEvent $event): bool
    {
        return $this->hasPermission($user, 'ViewPayload:AttendanceRawEvent') && $this->canAccessRecord($user, $event);
    }

    public function reprocess(User $user, AttendanceRawEvent $event): bool
    {
        return $this->hasPermission($user, 'Reprocess:AttendanceRawEvent') && $this->canAccessRecord($user, $event);
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
